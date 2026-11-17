<?php

namespace App\Services;

use App\CustomerStatus;
use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Customer;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanInstallment;
use App\Models\PaymentSubmission;
use App\Models\Repayment;

class DashboardMetricsService
{
    /** @return array<string, int|float> */
    public function summary(?int $branchId = null): array
    {
        $loans = Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
        $portfolio = (float) (clone $loans)->whereIn('status', [LoanStatus::Active, LoanStatus::InArrears])->sum('outstanding_principal');
        $par30Portfolio = (float) (clone $loans)->where('days_past_due', '>=', 30)->sum('outstanding_principal');
        $installments = LoanInstallment::query()->whereHas('loan', fn ($query) => $query->when($branchId, fn ($query) => $query->where('branch_id', $branchId)));
        $paid = (clone $installments)->selectRaw('COALESCE(SUM(principal_paid), 0) as principal, COALESCE(SUM(interest_paid + fees_paid + penalties_paid), 0) as income')->first();
        $disbursements = LoanDisbursement::query()->where('status', 'Disbursed')->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
        $assurance = (float) PaymentSubmission::query()->where('purpose', 'Assurance')->where('status', 'Approved')
            ->whereHas('application', fn ($query) => $query->when($branchId, fn ($query) => $query->where('branch_id', $branchId)))->sum('amount');
        $income = (float) $paid->income + (float) (clone $disbursements)->sum('deducted_fees') + $assurance + (float) (clone $loans)->sum('historical_upfront_income');
        $lines = JournalLine::query()->whereHas('entry', fn ($query) => $query->where('status', 'Posted'))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
        $expenses = (float) (clone $lines)->whereHas('account', fn ($query) => $query->where('type', 'Expense'))->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')->value('balance');
        $capital = (float) (clone $lines)->whereHas('account', fn ($query) => $query->where('type', 'Equity'))->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')->value('balance');

        return [
            'totalLoans' => (clone $loans)->count(),
            'totalPrincipal' => (float) (clone $loans)->sum('principal_amount'),
            'cashDisbursed' => (float) (clone $disbursements)->sum('net_amount') + (float) (clone $loans)->sum('historical_net_disbursement'),
            'principalRecovered' => (float) $paid->principal,
            'totalRepaid' => (float) $paid->principal + (float) $paid->income,
            'loanIncome' => round($income, 2),
            'recordedExpenses' => round($expenses, 2),
            'netLendingProfit' => round($income - $expenses, 2),
            'recordedCapital' => round($capital, 2),
            'capitalWithProfit' => round($capital + $income - $expenses, 2),
            'portfolio' => $portfolio,
            'activeBorrowers' => Customer::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('status', CustomerStatus::Active)->whereHas('loans', fn ($query) => $query->whereIn('status', [LoanStatus::Active, LoanStatus::InArrears]))->count(),
            'disbursedThisMonth' => (float) (clone $loans)->whereMonth('disbursed_at', now()->month)->whereYear('disbursed_at', now()->year)->sum('principal_amount'),
            'collectedToday' => (float) Repayment::query()->where('status', 'Posted')->whereNull('reversed_repayment_id')->whereHas('loan', fn ($query) => $query->when($branchId, fn ($query) => $query->where('branch_id', $branchId)))->whereDate('paid_at', today())->sum('amount'),
            'par30' => $portfolio > 0 ? round(($par30Portfolio / $portfolio) * 100, 2) : 0,
            'pendingApprovals' => LoanApplication::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('status', LoanApplicationStatus::PendingApproval)->count(),
            'loansInArrears' => (clone $loans)->where('status', LoanStatus::InArrears)->count(),
            'arrearsAmount' => (float) (clone $loans)->sum('arrears_amount'),
        ];
    }
}
