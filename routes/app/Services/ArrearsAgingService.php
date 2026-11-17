<?php

namespace App\Services;

use App\LoanStatus;
use App\Models\Loan;
use App\Models\NumberSequence;
use Carbon\CarbonImmutable;

class ArrearsAgingService
{
    public function __construct(private readonly PenaltyAssessmentService $penalties) {}

    /** @return array{processed: int, inArrears: int, casesCreated: int} */
    public function run(CarbonImmutable $businessDate): array
    {
        $processed = 0;
        $inArrears = 0;
        $casesCreated = 0;

        Loan::query()
            ->with(['installments', 'customer'])
            ->whereIn('status', [LoanStatus::Active, LoanStatus::InArrears])
            ->chunkById(100, function ($loans) use ($businessDate, &$processed, &$inArrears, &$casesCreated): void {
                foreach ($loans as $loan) {
                    $processed++;
                    $this->penalties->assessLoan($loan, $businessDate);
                    $loan->load('installments');
                    $overdueInstallments = $loan->installments->filter(
                        fn ($installment): bool => $installment->due_date->lt($businessDate) && ! $installment->isFullyPaid(),
                    );
                    $arrearsMinor = $overdueInstallments->sum(function ($installment): int {
                        return collect(['principal', 'interest', 'fees', 'penalties'])
                            ->sum(fn (string $component): int => max(0, $installment->outstandingFor($component)));
                    });
                    $oldestDueDate = $overdueInstallments->min('due_date');
                    $daysPastDue = $oldestDueDate ? CarbonImmutable::parse($oldestDueDate)->diffInDays($businessDate) : 0;

                    $loan->update([
                        'arrears_amount' => number_format($arrearsMinor / 100, 2, '.', ''),
                        'days_past_due' => $daysPastDue,
                        'status' => $arrearsMinor > 0 ? LoanStatus::InArrears : LoanStatus::Active,
                    ]);
                    $overdueInstallments->each(fn ($installment) => $installment->update(['status' => $installment->isFullyPaid() ? 'Paid' : 'Overdue']));

                    if ($arrearsMinor > 0) {
                        $inArrears++;

                        if (! $loan->collectionCases()->whereIn('status', ['Open', 'In Progress'])->exists()) {
                            $loan->collectionCases()->create([
                                'assigned_to' => $loan->customer->assigned_officer_id,
                                'case_number' => NumberSequence::issue('collection_case'),
                                'status' => 'Open',
                            ]);
                            $casesCreated++;
                        }
                    } else {
                        $loan->collectionCases()->whereIn('status', ['Open', 'In Progress'])->update(['status' => 'Resolved']);
                    }
                }
            });

        return compact('processed', 'inArrears', 'casesCreated');
    }
}
