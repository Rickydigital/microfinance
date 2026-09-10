<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanPenaltyCharge;
use App\Models\ProductPenaltyRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class PenaltyAssessmentService
{
    public function __construct(private readonly AccountingPostingService $accounting, private readonly CustomerCommunicationService $communication) {}

    /** @return array{charges: int, amount: float} */
    public function assessLoan(Loan $loan, CarbonImmutable $businessDate): array
    {
        $loan->loadMissing(['application.productVersion.penaltyRules', 'application.productVersion.accountMappings.account', 'application.creator', 'installments']);
        $charges = 0;
        $amount = 0.0;

        foreach ($loan->installments as $installment) {
            foreach ($loan->application->productVersion->penaltyRules->where('is_active', true) as $rule) {
                if (! $installment->due_date->addDays($rule->grace_days)->lt($businessDate) || $installment->isFullyPaid()) {
                    continue;
                }

                if ($rule->frequency === 'One-time' && $installment->penaltyCharges()->where('product_penalty_rule_id', $rule->id)->exists()) {
                    continue;
                }

                $charge = $this->createCharge($loan, $installment, $rule, $businessDate);

                if ($charge === null) {
                    continue;
                }

                $charges++;
                $amount += (float) $charge->amount;
                $this->postAccounting($charge, $loan);
                $this->communication->penaltyAdded($loan->customer, $loan->loan_number, (float) $charge->amount, $rule->name);
            }
        }

        return ['charges' => $charges, 'amount' => round($amount, 2)];
    }

    private function createCharge(Loan $loan, LoanInstallment $installment, ProductPenaltyRule $rule, CarbonImmutable $businessDate): ?LoanPenaltyCharge
    {
        return DB::transaction(function () use ($loan, $installment, $rule, $businessDate): ?LoanPenaltyCharge {
            $installment = $loan->installments()->lockForUpdate()->findOrFail($installment->id);

            if ($installment->penaltyCharges()->where('product_penalty_rule_id', $rule->id)->whereDate('charge_date', $businessDate)->exists()) {
                return null;
            }

            $base = match ($rule->calculation_basis) {
                'Overdue Principal' => max(0, $installment->outstandingFor('principal')) / 100,
                'Total Arrears' => $loan->installments->filter(fn ($item) => $item->due_date->lt($businessDate))->sum(fn ($item): float => collect(['principal', 'interest', 'fees'])->sum(fn (string $component): int => max(0, $item->outstandingFor($component))) / 100),
                default => collect(['principal', 'interest', 'fees'])->sum(fn (string $component): int => max(0, $installment->outstandingFor($component))) / 100,
            };
            $calculated = $rule->calculation_method === 'Percentage' ? $base * (float) $rule->value : (float) $rule->value;
            $alreadyCharged = (float) $installment->penaltyCharges()->where('product_penalty_rule_id', $rule->id)->sum('amount');
            $remainingCap = $rule->maximum_amount === null ? $calculated : max(0, (float) $rule->maximum_amount - $alreadyCharged);
            $chargeAmount = round(min($calculated, $remainingCap), 2);

            if ($chargeAmount <= 0) {
                return null;
            }

            $charge = $installment->penaltyCharges()->create(['loan_id' => $loan->id, 'product_penalty_rule_id' => $rule->id, 'charge_date' => $businessDate, 'calculation_base' => $base, 'amount' => $chargeAmount, 'status' => 'Pending Accounting']);
            $installment->increment('penalties_due', $chargeAmount);

            return $charge;
        });
    }

    private function postAccounting(LoanPenaltyCharge $charge, Loan $loan): void
    {
        $mappings = $loan->application->productVersion->accountMappings->keyBy('purpose');

        if (! $mappings->has('loan_receivable') || ! $mappings->has('penalty_income')) {
            return;
        }

        try {
            $entry = $this->accounting->post($loan->application->creator, "Penalty charge for {$loan->loan_number}", LoanPenaltyCharge::class, $charge->id, [
                ['account_code' => $mappings['loan_receivable']->account->code, 'debit' => $charge->amount],
                ['account_code' => $mappings['penalty_income']->account->code, 'credit' => $charge->amount],
            ], $loan->branch_id);
            $charge->update(['journal_entry_id' => $entry->id, 'status' => 'Posted']);
        } catch (Throwable) {
            $charge->update(['status' => 'Pending Accounting']);
        }
    }
}
