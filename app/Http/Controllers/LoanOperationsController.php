<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LoanOperationsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:loan.view')];
    }

    public function calendar(Request $request): View
    {
        $month = CarbonImmutable::parse($request->string('month')->toString() ?: today()->format('Y-m').'-01')->startOfMonth();
        $installments = LoanInstallment::query()->with('loan.customer')
            ->whereBetween('due_date', [$month, $month->endOfMonth()])
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))
            ->orderBy('due_date')->get();
        $events = $installments->groupBy(fn (LoanInstallment $installment): string => $installment->due_date->format('Y-m-d'));
        $metrics = [
            'due' => $installments->sum(fn (LoanInstallment $installment): float => $this->outstanding($installment)),
            'overdue' => $installments->filter(fn (LoanInstallment $installment): bool => $installment->due_date->isBefore(today()) && ! $installment->isFullyPaid())->sum(fn (LoanInstallment $installment): float => $this->outstanding($installment)),
            'paid' => $installments->filter(fn (LoanInstallment $installment): bool => $installment->isFullyPaid())->sum(fn (LoanInstallment $installment): float => (float) $installment->principal_paid + (float) $installment->interest_paid + (float) $installment->fees_paid + (float) $installment->penalties_paid),
            'maturing' => Loan::query()->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))->whereBetween('maturity_date', [$month, $month->endOfMonth()])->count(),
        ];

        return view('loan-operations.calendar', compact('month', 'events', 'metrics'));
    }

    public function ageing(Request $request): View
    {
        $installments = LoanInstallment::query()->with('loan.customer')->whereDate('due_date', '<', today())->where('status', '!=', 'Paid')
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->orderBy('due_date')->get();
        $buckets = collect([
            '0–5 days' => [0, 5, '#e9a400'], '6–30 days' => [6, 30, '#ef8b00'], '31–60 days' => [31, 60, '#e96322'],
            '61–90 days' => [61, 90, '#df334e'], 'Over 90 days' => [91, PHP_INT_MAX, '#a30f2b'],
        ])->map(function (array $range, string $label) use ($installments): array {
            $rows = $installments->filter(function (LoanInstallment $installment) use ($range): bool {
                $days = $installment->due_date->diffInDays(today());

                return $days >= $range[0] && $days <= $range[1];
            });

            return ['label' => $label, 'color' => $range[2], 'rows' => $rows, 'count' => $rows->count(), 'amount' => $rows->sum(fn (LoanInstallment $installment): float => $this->outstanding($installment))];
        });

        $totalAmount = (float) $buckets->sum('amount');
        $totalCount = (int) $buckets->sum('count');
        $metrics = [
            'accounts' => $installments->pluck('loan_id')->unique()->count(),
            'average_days' => $installments->isEmpty() ? 0 : (int) round($installments->avg(fn (LoanInstallment $item): int => $item->due_date->diffInDays(today()))),
            'par30' => $totalAmount > 0 ? (float) $buckets->slice(2)->sum('amount') / $totalAmount * 100 : 0,
            'severe' => (float) $buckets->last()['amount'],
        ];

        return view('loan-operations.ageing', compact('buckets', 'totalAmount', 'totalCount', 'metrics'));
    }

    private function outstanding(LoanInstallment $installment): float
    {
        return max(0, (float) $installment->principal_due - (float) $installment->principal_paid)
            + max(0, (float) $installment->interest_due - (float) $installment->interest_paid)
            + max(0, (float) $installment->fees_due - (float) $installment->fees_paid)
            + max(0, (float) $installment->penalties_due - (float) $installment->penalties_paid);
    }
}
