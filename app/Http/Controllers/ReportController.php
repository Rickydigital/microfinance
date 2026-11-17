<?php

namespace App\Http\Controllers;

use App\Exports\ReportWorkbookExport;
use App\Models\CollectionCase;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\Repayment;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function (Request $request, Closure $next): Response {
                abort_unless($request->user()?->hasAnyPermission([
                    'report.operational', 'report.portfolio', 'report.collections',
                    'report.risk', 'report.financial', 'report.audit',
                ]), 403);

                return $next($request);
            },
        ];
    }

    public function index(Request $request, string $report = 'portfolio-summary'): View
    {
        $definition = $this->authorizeReport($request, $report);
        $branchId = $request->user()->branch_id;
        $period = $request->string('period')->toString() ?: 'all-time';
        [$from, $to] = $this->reportPeriod($request, $period, $branchId);

        $periodLoans = Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->whereBetween('disbursed_at', [$from, $to]);
        $periodRepayments = Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->whereBetween('paid_at', [$from, $to]);

        $metrics = [
            'active_loans' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('status', 'Active')->count(),
            'outstanding_principal' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('outstanding_principal'),
            'collections' => Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->when($from, fn ($query) => $query->where('paid_at', '>=', $from))->when($to, fn ($query) => $query->where('paid_at', '<=', $to))->sum('amount'),
            'disbursements' => (clone $periodLoans)->sum('principal_amount'),
            'disbursement_count' => (clone $periodLoans)->count(),
            'repayment_count' => (clone $periodRepayments)->count(),
            'open_cases' => CollectionCase::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->whereNotIn('status', ['Closed', 'Resolved'])->count(),
            'debits' => JournalLine::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('debit'),
            'credits' => JournalLine::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('credit'),
        ];

        // Scoped, aggregate-only BI data; no cross-branch customer information is exposed.
        $loanStatusDistribution = Loan::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $collectionTrend = Repayment::query()
            ->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))
            ->where('status', 'Posted')
            ->whereBetween('paid_at', [$from, $to])
            ->get(['paid_at', 'amount'])
            ->groupBy(fn (Repayment $repayment): string => $repayment->paid_at->format('Y-m'))
            ->map(fn ($repayments, string $period): object => (object) [
                'period' => $period,
                'total' => $repayments->sum('amount'),
            ])
            ->sortBy('period')
            ->values();

        $disbursementTrend = Loan::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('disbursed_at', [$from, $to])
            ->get(['disbursed_at', 'principal_amount'])
            ->groupBy(fn (Loan $loan): string => $loan->disbursed_at->format('Y-m'))
            ->map(fn ($loans, string $trendPeriod): object => (object) ['period' => $trendPeriod, 'total' => $loans->sum('principal_amount')]);
        $trendPeriods = $collectionTrend->pluck('period')->merge($disbursementTrend->keys())->unique()->sort()->values();
        $cashflowTrend = $trendPeriods->map(fn (string $trendPeriod): object => (object) [
            'period' => $trendPeriod,
            'collections' => (float) ($collectionTrend->firstWhere('period', $trendPeriod)?->total ?? 0),
            'disbursements' => (float) ($disbursementTrend->get($trendPeriod)?->total ?? 0),
        ]);

        $totalLoans = $loanStatusDistribution->sum('total');
        $maximumCollections = max(1, (float) $collectionTrend->max('total'));
        $maximumCashflow = max(1, (float) $cashflowTrend->max(fn (object $item): float => max($item->collections, $item->disbursements)));
        $trendWidth = 1000;
        $trendHeight = 250;
        $trendCount = max(1, $cashflowTrend->count() - 1);
        $trendPoints = [
            'disbursements' => $cashflowTrend->values()->map(fn (object $item, int $index): string => round($index / $trendCount * $trendWidth, 2).','.round($trendHeight - ($item->disbursements / $maximumCashflow * ($trendHeight - 20)), 2))->implode(' '),
            'collections' => $cashflowTrend->values()->map(fn (object $item, int $index): string => round($index / $trendCount * $trendWidth, 2).','.round($trendHeight - ($item->collections / $maximumCashflow * ($trendHeight - 20)), 2))->implode(' '),
        ];
        $productPerformance = Loan::query()->with('application.productVersion.product')->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->whereBetween('disbursed_at', [$from, $to])->get()
            ->groupBy(fn (Loan $loan): string => $loan->application?->productVersion?->product?->name ?? 'Unclassified')
            ->map(fn ($loans, string $product): object => (object) ['product' => $product, 'loans' => $loans->count(), 'disbursed' => (float) $loans->sum('principal_amount'), 'outstanding' => (float) $loans->sum('outstanding_principal')])->sortByDesc('disbursed')->take(6)->values();
        $maximumProduct = max(1, (float) $productPerformance->max('disbursed'));
        $repaymentMethods = Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->whereBetween('paid_at', [$from, $to])
            ->selectRaw('payment_method, COUNT(*) total, SUM(amount) amount')->groupBy('payment_method')->orderByDesc('amount')->get();
        $riskBuckets = collect([
            ['label' => 'Current', 'minimum' => 0, 'maximum' => 0, 'color' => '#16a085'],
            ['label' => '1–30 days', 'minimum' => 1, 'maximum' => 30, 'color' => '#e0a126'],
            ['label' => '31–90 days', 'minimum' => 31, 'maximum' => 90, 'color' => '#ea7330'],
            ['label' => 'Over 90 days', 'minimum' => 91, 'maximum' => PHP_INT_MAX, 'color' => '#cf4052'],
        ])->map(function (array $bucket) use ($branchId): array {
            $query = Loan::query()->when($branchId, fn ($builder) => $builder->where('branch_id', $branchId))->whereBetween('days_past_due', [$bucket['minimum'], $bucket['maximum']]);

            return $bucket + ['loans' => $query->count(), 'outstanding' => (float) $query->sum('outstanding_principal')];
        });
        $reportInsights = [
            'period_label' => $from->format('d M Y').' – '.$to->format('d M Y'),
            'collection_rate' => $cashflowTrend->sum('disbursements') > 0 ? $cashflowTrend->sum('collections') / $cashflowTrend->sum('disbursements') * 100 : 0,
            'portfolio_at_risk' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('outstanding_principal') > 0
                ? Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('days_past_due', '>', 30)->sum('outstanding_principal') / Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('outstanding_principal') * 100 : 0,
        ];

        $reportGroups = config('microfinance.reports');

        return view('reports.index', compact('report', 'definition', 'metrics', 'reportGroups', 'loanStatusDistribution', 'collectionTrend', 'totalLoans', 'maximumCollections', 'period', 'from', 'to', 'cashflowTrend', 'maximumCashflow', 'trendPoints', 'reportInsights', 'productPerformance', 'maximumProduct', 'repaymentMethods', 'riskBuckets'));
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        $definition = $this->authorizeReport($request, $report);
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to'])->endOfDay() : null;
        $export = new ReportWorkbookExport($definition['dataset'], $request->user()->branch_id, $from, $to, $request->user()->full_name);
        $filename = $report.'-report-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(fn () => $export->writeTo('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /** @return array{label: string, dataset: string, group: string, group_label: string, permission: string} */
    private function authorizeReport(Request $request, string $report): array
    {
        $report = $this->normalizeReport($report);

        foreach (config('microfinance.reports') as $groupKey => $group) {
            foreach ($group['reports'] as $slug => $definition) {
                if ($slug !== $report) {
                    continue;
                }

                $permission = $definition['permission'] ?? $group['permission'];
                abort_unless($request->user()->can($permission), 403);

                return $definition + [
                    'group' => $groupKey,
                    'group_label' => $group['label'],
                    'permission' => $permission,
                ];
            }
        }

        abort(404);
    }

    private function normalizeReport(string $report): string
    {
        return [
            'operational' => 'branch-performance',
            'portfolio' => 'portfolio-summary',
            'financial' => 'balance-sheet',
            'risk' => 'portfolio-quality-par',
            'collections' => 'collection-performance',
            'audit' => 'audit-trail',
        ][$report] ?? $report;
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function reportPeriod(Request $request, string $period, ?int $branchId): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'last-7-days' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            'last-30-days' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'this-quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'this-year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [CarbonImmutable::parse($request->input('from', $now->startOfMonth()))->startOfDay(), CarbonImmutable::parse($request->input('to', $now))->endOfDay()],
            'last-12-months' => [$now->subMonths(11)->startOfMonth(), $now->endOfDay()],
            default => $this->availableDataPeriod($branchId, $now),
        };
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function availableDataPeriod(?int $branchId, CarbonImmutable $fallback): array
    {
        $firstLoan = Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->min('disbursed_at');
        $lastLoan = Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->max('disbursed_at');
        $firstRepayment = Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->min('paid_at');
        $lastRepayment = Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->max('paid_at');
        $first = collect([$firstLoan, $firstRepayment])->filter()->min();
        $last = collect([$lastLoan, $lastRepayment])->filter()->max();

        return [
            $first ? CarbonImmutable::parse($first)->startOfDay() : $fallback->startOfYear(),
            $last ? CarbonImmutable::parse($last)->endOfDay() : $fallback->endOfDay(),
        ];
    }
}
