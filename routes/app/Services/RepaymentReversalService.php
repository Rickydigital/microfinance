<?php

namespace App\Services;

use App\LoanStatus;
use App\Models\CashTransaction;
use App\Models\Loan;
use App\Models\LoanTransaction;
use App\Models\NumberSequence;
use App\Models\Repayment;
use App\Models\User;
use App\TransactionStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

class RepaymentReversalService
{
    public function __construct(
        private readonly AccountingPostingService $accounting,
        private readonly CashTransactionService $cashTransactions,
    ) {}

    public function reverse(Repayment $repayment, User $reverser, string $reason): Repayment
    {
        return DB::transaction(function () use ($repayment, $reverser, $reason): Repayment {
            $repayment = Repayment::query()->with('allocations.installment')->lockForUpdate()->findOrFail($repayment->id);

            if ($repayment->status !== TransactionStatus::Posted->value || $repayment->received_by === $reverser->id) {
                throw new DomainException('A posted repayment must be reversed by a different authorized employee.');
            }

            $loan = Loan::query()->with('application.productVersion.accountMappings.account')->lockForUpdate()->findOrFail($repayment->loan_id);
            $originalTransaction = LoanTransaction::query()->where('repayment_id', $repayment->id)->where('status', TransactionStatus::Posted)->lockForUpdate()->firstOrFail();
            $totals = ['principal' => 0, 'interest' => 0, 'fees' => 0, 'penalties' => 0];

            foreach ($repayment->allocations as $allocation) {
                $minorUnits = $this->toMinorUnits($allocation->amount);
                $allocation->installment->reverseAllocation($allocation->component, $minorUnits);
                $totals[$allocation->component] += $minorUnits;
            }

            $reversal = Repayment::query()->create([
                'loan_id' => $loan->id,
                'receipt_number' => NumberSequence::issue('repayment'),
                'amount' => $repayment->amount,
                'status' => TransactionStatus::Posted->value,
                'payment_method' => $repayment->payment_method,
                'external_reference' => $repayment->external_reference,
                'paid_at' => now(),
                'received_by' => $reverser->id,
                'reversed_repayment_id' => $repayment->id,
                'reversal_reason' => $reason,
            ]);

            $loan->transactions()->create([
                'repayment_id' => $reversal->id,
                'created_by' => $reverser->id,
                'transaction_number' => NumberSequence::issue('transaction'),
                'type' => 'Repayment Reversal',
                'status' => TransactionStatus::Posted,
                'amount' => -((float) $repayment->amount),
                'principal_amount' => -($totals['principal'] / 100),
                'interest_amount' => -($totals['interest'] / 100),
                'fee_amount' => -($totals['fees'] / 100),
                'penalty_amount' => -($totals['penalties'] / 100),
                'reverses_transaction_id' => $originalTransaction->id,
                'reason' => $reason,
                'posted_at' => now(),
            ]);
            $originalTransaction->update(['status' => TransactionStatus::Reversed]);
            $repayment->update(['status' => TransactionStatus::Reversed->value, 'reversal_reason' => $reason]);
            $loan->update(['outstanding_principal' => number_format((float) $loan->outstanding_principal + ($totals['principal'] / 100), 2, '.', ''), 'status' => LoanStatus::Active]);

            $cashTransaction = CashTransaction::query()->where('source_type', $repayment->getMorphClass())->where('source_id', $repayment->id)->where('status', 'Posted')->firstOrFail();
            $this->cashTransactions->reverse($cashTransaction, $reverser, $reason);
            $mappings = $loan->application->productVersion->accountMappings->keyBy('purpose');
            $channelPurpose = match ($repayment->payment_method) {
                'Bank' => 'bank_account',
                'Mobile Money' => 'mobile_money_account',
                default => 'cash_account',
            };
            $this->accounting->post($reverser, "Repayment reversal {$repayment->receipt_number}", Repayment::class, $reversal->id, [
                ['account_code' => $mappings->get('loan_receivable')?->account?->code ?? '1100', 'debit' => $this->fromMinorUnits($totals['principal'])],
                ['account_code' => $mappings->get('interest_income')?->account?->code ?? '4000', 'debit' => $this->fromMinorUnits($totals['interest'])],
                ['account_code' => $mappings->get('fee_income')?->account?->code ?? '4100', 'debit' => $this->fromMinorUnits($totals['fees'])],
                ['account_code' => $mappings->get('penalty_income')?->account?->code ?? '4200', 'debit' => $this->fromMinorUnits($totals['penalties'])],
                ['account_code' => $mappings->get($channelPurpose)?->account?->code ?? '1000', 'credit' => $repayment->amount],
            ], $loan->branch_id);

            return $reversal;
        });
    }

    private function toMinorUnits(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromMinorUnits(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
