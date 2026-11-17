<?php

namespace App\Http\Controllers;

use App\Exports\BotReturnWorkbookExport;
use App\Models\Branch;
use App\Models\Loan;
use App\Services\BotReturnService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BotReturnController extends Controller
{
    public function index(Request $request, BotReturnService $service): View
    {
        $this->authorizeAccess($request);
        [$year, $quarter, $branchId, $periodMode, $customStart, $customEnd] = $this->filters($request);
        $return = $service->build($year, $quarter, $branchId, $customStart, $customEnd);
        $quarterTrend = collect(range(7, 0))->map(function (int $offset) use ($service, $return, $branchId): array {
            $date = $return['period']['end']->subQuarters($offset);
            $snapshot = $service->build($date->year, $date->quarter, $branchId);

            return [
                'label' => 'Q'.$date->quarter.' '.$date->format('y'),
                'portfolio' => $snapshot['metrics']['gross_portfolio'],
                'disbursements' => $snapshot['metrics']['quarter_disbursements'],
                'collections' => $snapshot['metrics']['quarter_collections'],
                'par30' => $snapshot['metrics']['par30'],
            ];
        });
        $branches = $request->user()->branch_id
            ? Branch::query()->whereKey($request->user()->branch_id)->get()
            : Branch::query()->where('is_active', true)->orderBy('name')->get();
        $modalDefinitions = [
            'gross-portfolio' => ['Gross loan portfolio', 'Stock figure at quarter end', 'For every loan disbursed on or before quarter end: original principal minus all posted principal allocations paid on or before quarter end. Fully repaid loans contribute zero.'],
            'active-borrowers' => ['Active borrowers', 'Unique customer count', 'Customers are counted once when at least one loan has outstanding principal at quarter end. A customer with several loans is still one borrower.'],
            'quarter-flow' => ['Quarter disbursements', 'Flow inside the quarter', 'Sum of original principal for loans whose disbursement date falls between the first and final day of the selected quarter.'],
            'portfolio-risk' => ['PAR over 30 days', 'Portfolio risk ratio', 'Outstanding principal in aging bands above 30 days divided by gross outstanding portfolio, multiplied by 100.'],
            'provision' => ['Required provision', 'Aging-weighted reserve', 'Outstanding in each aging band multiplied by that band’s configured rate, then all bands are added together.'],
            'source-loans' => ['Loan records used', 'Operational source', 'These are loans disbursed by quarter end that still have reconstructed outstanding principal.'],
            'source-activity' => ['Quarter activity used', 'Operational source', 'Disbursements use loan disbursement dates. Collections use posted repayment dates. Only activity inside the selected quarter is included.'],
            'source-ledger' => ['Accounting ledger source', 'Why ledger schedules can be zero', 'Balance Sheet, Income Statement and Liquid Assets only use journal entries with status Posted. Loan imports without accounting journals cannot safely be presented as audited ledger balances.'],
        ];

        return view('bot-returns.index', compact('return', 'branches', 'year', 'quarter', 'branchId', 'periodMode', 'customStart', 'customEnd', 'modalDefinitions', 'quarterTrend'));
    }

    public function export(Request $request, BotReturnService $service): StreamedResponse
    {
        $this->authorizeAccess($request);
        [$year, $quarter, $branchId, , $customStart, $customEnd] = $this->filters($request);
        $export = new BotReturnWorkbookExport($service->build($year, $quarter, $branchId, $customStart, $customEnd));

        return response()->streamDownload(fn () => $export->writeTo('php://output'), "BOT-return-{$year}-Q{$quarter}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(Request $request, BotReturnService $service): Response
    {
        $this->authorizeAccess($request);
        [$year, $quarter, $branchId, , $customStart, $customEnd] = $this->filters($request);
        $return = $service->build($year, $quarter, $branchId, $customStart, $customEnd);

        return Pdf::loadView('pdf.bot-return', compact('return'))->setPaper('a4', 'landscape')->stream("BOT-return-{$year}-Q{$quarter}.pdf");
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()?->can('report.portfolio'), 403);
    }

    /** @return array{int, int, int|null, string, CarbonImmutable|null, CarbonImmutable|null} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'period_mode' => ['nullable', 'in:quarter,year,custom'],
            'from' => ['nullable', 'date', 'required_if:period_mode,custom'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_if:period_mode,custom'],
        ]);

        $branchId = $request->user()->branch_id ?: (isset($validated['branch_id']) ? (int) $validated['branch_id'] : null);
        $latestActivity = Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->max('disbursed_at');
        $defaultDate = $latestActivity ? CarbonImmutable::parse($latestActivity) : now();

        $year = (int) ($validated['year'] ?? $defaultDate->year);
        $quarter = (int) ($validated['quarter'] ?? $defaultDate->quarter);
        $periodMode = $validated['period_mode'] ?? 'quarter';
        $customStart = match ($periodMode) {
            'year' => CarbonImmutable::create($year)->startOfYear(),
            'custom' => CarbonImmutable::parse($validated['from']),
            default => null,
        };
        $customEnd = match ($periodMode) {
            'year' => CarbonImmutable::create($year)->endOfYear(),
            'custom' => CarbonImmutable::parse($validated['to']),
            default => null,
        };

        return [$year, $quarter, $branchId, $periodMode, $customStart, $customEnd];
    }
}
