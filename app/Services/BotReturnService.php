<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\RepaymentAllocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BotReturnService
{
    /** @return array<string, mixed> */
    public function build(int $year, int $quarter, ?int $branchId, ?CarbonImmutable $customStart = null, ?CarbonImmutable $customEnd = null): array
    {
        $start = $customStart?->startOfDay() ?? CarbonImmutable::create($year, (($quarter - 1) * 3) + 1)->startOfMonth();
        $end = $customEnd?->endOfDay() ?? $start->addMonths(3)->subDay()->endOfDay();
        $accounts = $this->accountBalances($end, $branchId);
        $portfolio = $this->portfolioAt($end, $branchId);
        $aging = $this->aging($portfolio);
        $flows = $this->flows($start, $end, $branchId);
        $assets = $accounts->where('type', 'Asset')->sum('balance');
        $liabilities = $accounts->where('type', 'Liability')->sum('balance');
        $capital = $accounts->whereIn('type', ['Equity', 'Capital'])->sum('balance');
        $loanControlAccounts = $accounts->filter(fn (array $row): bool => preg_match('/loan.*receiv|receiv.*loan/i', $row['name']) === 1);
        $loanControl = $loanControlAccounts->sum('balance');
        $grossPortfolio = $portfolio->sum('outstanding');

        return [
            'period' => compact('year', 'quarter', 'start', 'end'),
            'balance_sheet' => compact('assets', 'liabilities', 'capital', 'accounts'),
            'income_statement' => $flows,
            'portfolio' => $portfolio,
            'aging' => $aging,
            'liquid_assets' => $accounts->filter(fn (array $row): bool => $row['type'] === 'Asset' && preg_match('/cash|bank|mobile money|till/i', $row['name']) === 1)->values(),
            'interest_rates' => $this->interestRates($portfolio),
            'borrowers' => ['gender' => $this->breakdown($portfolio, 'gender'), 'sector' => $this->breakdown($portfolio, 'sector')],
            'validations' => [
                $this->check('Balance sheet equation', $assets, $liabilities + $capital, true),
                $this->check('Portfolio to loan control account', $grossPortfolio, $loanControl, $loanControlAccounts->isNotEmpty()),
            ],
            'metrics' => [
                'gross_portfolio' => $grossPortfolio,
                'active_borrowers' => $portfolio->pluck('customer_id')->unique()->count(),
                'quarter_disbursements' => $flows['disbursements'],
                'required_provision' => $aging->sum('provision'),
                'par30' => $grossPortfolio > 0 ? $aging->where('minimum_days', '>=', 31)->sum('outstanding') / $grossPortfolio * 100 : 0,
                'quarter_collections' => (float) Repayment::query()->where('status', 'Posted')->whereBetween('paid_at', [$start, $end])->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->sum('amount'),
            ],
            'sources' => [
                'loans_at_period_end' => $portfolio->count(),
                'quarter_disbursements' => Loan::query()->whereBetween('disbursed_at', [$start, $end])->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->count(),
                'quarter_collections' => Repayment::query()->where('status', 'Posted')->whereBetween('paid_at', [$start, $end])->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->count(),
                'posted_journals' => JournalEntry::query()->where('status', 'Posted')->where('posting_date', '<=', $end)->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->count(),
            ],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function accountBalances(CarbonImmutable $end, ?int $branchId): Collection
    {
        $totals = JournalLine::query()->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'Posted')->where('journal_entries.posting_date', '<=', $end)
            ->when($branchId, fn ($query) => $query->where('journal_lines.branch_id', $branchId))
            ->selectRaw('chart_of_account_id, SUM(debit) debit, SUM(credit) credit')->groupBy('chart_of_account_id')->get()->keyBy('chart_of_account_id');

        return ChartOfAccount::query()->where('is_active', true)->orderBy('code')->get()->map(function (ChartOfAccount $account) use ($totals): array {
            $total = $totals->get($account->id);
            $debit = (float) ($total->debit ?? 0);
            $credit = (float) ($total->credit ?? 0);
            $creditNormal = in_array($account->type, ['Liability', 'Equity', 'Capital', 'Income', 'Revenue'], true);

            return ['code' => $account->code, 'name' => $account->name, 'type' => $account->type, 'balance' => $creditNormal ? $credit - $debit : $debit - $credit];
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function portfolioAt(CarbonImmutable $end, ?int $branchId): Collection
    {
        $loans = Loan::query()->with(['customer.economicProfiles', 'application.productVersion.product'])->whereDate('disbursed_at', '<=', $end)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->get();
        $paid = RepaymentAllocation::query()->join('repayments', 'repayments.id', '=', 'repayment_allocations.repayment_id')
            ->join('loan_installments', 'loan_installments.id', '=', 'repayment_allocations.loan_installment_id')
            ->where('component', 'principal')->where('repayments.status', 'Posted')->where('repayments.paid_at', '<=', $end)
            ->selectRaw('loan_installments.loan_id, SUM(repayment_allocations.amount) paid')->groupBy('loan_installments.loan_id')->pluck('paid', 'loan_id');

        return $loans->map(function (Loan $loan) use ($paid, $end): array {
            $outstanding = max(0, (float) $loan->principal_amount - (float) ($paid[$loan->id] ?? 0));
            $profile = $loan->customer->economicProfiles->sortByDesc('effective_from')->first();

            return [
                'loan_number' => $loan->loan_number, 'customer_id' => $loan->customer_id, 'borrower' => trim($loan->customer->first_name.' '.$loan->customer->last_name),
                'gender' => $loan->customer->type === 'Group' ? 'Group / Entity' : ($loan->customer->gender ?: 'Not recorded'),
                'sector' => $profile?->income_source ?: $profile?->occupation ?: 'Unclassified',
                'product' => $loan->application?->productVersion?->product?->name ?? 'Unclassified',
                'rate' => (float) ($loan->application?->productVersion?->interest_rate ?? 0), 'principal' => (float) $loan->principal_amount,
                'outstanding' => $outstanding, 'days_past_due' => $outstanding > 0 && $loan->maturity_date?->lt($end) ? $loan->maturity_date->diffInDays($end) : 0,
                'maturity_date' => $loan->maturity_date,
            ];
        })->where('outstanding', '>', 0)->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function aging(Collection $portfolio): Collection
    {
        $bands = [['Current (0-30 days)', 0, 30, 0], ['Watch (31-60 days)', 31, 60, 5], ['Substandard (61-90 days)', 61, 90, 25], ['Doubtful (91-180 days)', 91, 180, 50], ['Loss (over 180 days)', 181, PHP_INT_MAX, 100]];

        return collect($bands)->map(function (array $band) use ($portfolio): array {
            $loans = $portfolio->filter(fn (array $loan): bool => $loan['days_past_due'] >= $band[1] && $loan['days_past_due'] <= $band[2]);
            $outstanding = $loans->sum('outstanding');

            return ['label' => $band[0], 'minimum_days' => $band[1], 'rate' => $band[3], 'loans' => $loans->count(), 'borrowers' => $loans->pluck('customer_id')->unique()->count(), 'outstanding' => $outstanding, 'provision' => $outstanding * $band[3] / 100];
        });
    }

    /** @return array<string, mixed> */
    private function flows(CarbonImmutable $start, CarbonImmutable $end, ?int $branchId): array
    {
        $rows = JournalLine::query()->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_lines.chart_of_account_id')
            ->where('journal_entries.status', 'Posted')->whereBetween('journal_entries.posting_date', [$start, $end])->when($branchId, fn ($query) => $query->where('journal_lines.branch_id', $branchId))
            ->whereIn('chart_of_accounts.type', ['Income', 'Revenue', 'Expense'])->selectRaw('chart_of_accounts.type, chart_of_accounts.code, chart_of_accounts.name, SUM(debit) debit, SUM(credit) credit')
            ->groupBy('chart_of_accounts.type', 'chart_of_accounts.code', 'chart_of_accounts.name')->get()->map(fn ($row): array => ['code' => $row->code, 'name' => $row->name, 'type' => $row->type, 'amount' => in_array($row->type, ['Income', 'Revenue'], true) ? (float) $row->credit - (float) $row->debit : (float) $row->debit - (float) $row->credit]);
        $disbursements = Loan::query()->whereBetween('disbursed_at', [$start, $end])->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('principal_amount');

        return ['rows' => $rows, 'income' => $rows->whereIn('type', ['Income', 'Revenue'])->sum('amount'), 'expenses' => $rows->where('type', 'Expense')->sum('amount'), 'disbursements' => (float) $disbursements];
    }

    private function interestRates(Collection $portfolio): Collection
    {
        return $portfolio->groupBy('product')->map(fn (Collection $loans, string $product): array => ['product' => $product, 'loans' => $loans->count(), 'minimum' => $loans->min('rate') ?? 0, 'maximum' => $loans->max('rate') ?? 0, 'weighted_average' => $loans->sum('outstanding') > 0 ? $loans->sum(fn (array $loan): float => $loan['rate'] * $loan['outstanding']) / $loans->sum('outstanding') : 0])->values();
    }

    private function breakdown(Collection $portfolio, string $field): Collection
    {
        return $portfolio->groupBy($field)->map(fn (Collection $loans, string $label): array => ['label' => $label, 'borrowers' => $loans->pluck('customer_id')->unique()->count(), 'loans' => $loans->count(), 'outstanding' => $loans->sum('outstanding')])->sortByDesc('outstanding')->values();
    }

    /** @return array<string, mixed> */
    private function check(string $label, float $left, float $right, bool $configured): array
    {
        return ['label' => $label, 'left' => $left, 'right' => $right, 'difference' => $left - $right, 'status' => ! $configured ? 'Mapping required' : (abs($left - $right) < 0.01 ? 'Passed' : 'Review')];
    }
}
