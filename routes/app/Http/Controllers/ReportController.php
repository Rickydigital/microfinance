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

        $metrics = [
            'active_loans' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('status', 'Active')->count(),
            'outstanding_principal' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('outstanding_principal'),
            'collections' => Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->sum('amount'),
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
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, SUM(amount) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $totalLoans = $loanStatusDistribution->sum('total');
        $maximumCollections = max(1, (float) $collectionTrend->max('total'));

        $reportGroups = config('microfinance.reports');

        return view('reports.index', compact('report', 'definition', 'metrics', 'reportGroups', 'loanStatusDistribution', 'collectionTrend', 'totalLoans', 'maximumCollections'));
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
}
