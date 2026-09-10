<?php

namespace App\Services;

use App\Data\RepaymentData;
use App\LoanStatus;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Repayment;
use App\Models\User;
use App\TransactionStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

class RepaymentPostingService
{
    /** @var list<string> */
    private const ALLOCATION_ORDER = ['penalties', 'fees', 'interest', 'principal'];

    public function __construct(
        private readonly AccountingPostingService $accounting,
        private readonly CashTransactionService $cashTransactions,
    ) {}

    public function post(RepaymentData $data): Repayment
    {
        return DB::transaction(function () use ($data): Repayment {
            $existingRepayment = $this->findIdempotentRepayment($data);

            if ($existingRepayment !== null) {
                return $existingRepayment;
            }

            $loan = Loan::query()->with('application.productVersion.accountMappings.account')->lockForUpdate()->findOrFail($data->loanId);

            if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::InArrears], true)) {
                throw new DomainException('Repayments may only be posted to active or arrears loans.');
            }

            $repayment = $loan->repayments()->create([
                'receipt_number' => $data->receiptNumber,
                'amount' => $data->amount,
                'status' => TransactionStatus::Posted->value,
                'payment_method' => $data->paymentMethod,
                'external_reference' => $data->externalReference,
                'paid_at' => $data->paidAt,
                'received_by' => $data->receivedBy,
                'idempotency_key' => $data->idempotencyKey,
            ]);

            $allocated = $this->allocate($loan, $repayment, $this->toMinorUnits($data->amount));

            $loan->transactions()->create([
                'repayment_id' => $repayment->id,
                'created_by' => $data->receivedBy,
                'transaction_number' => $data->transactionNumber,
                'type' => 'Repayment',
                'status' => TransactionStatus::Posted,
                'amount' => $data->amount,
                'principal_amount' => $this->fromMinorUnits($allocated['principal']),
                'interest_amount' => $this->fromMinorUnits($allocated['interest']),
                'fee_amount' => $this->fromMinorUnits($allocated['fees']),
                'penalty_amount' => $this->fromMinorUnits($allocated['penalties']),
                'posted_at' => $data->paidAt,
            ]);

            $outstandingPrincipal = max(
                0,
                $this->toMinorUnits($loan->outstanding_principal) - $allocated['principal'],
            );

            $loan->outstanding_principal = $this->fromMinorUnits($outstandingPrincipal);

            if ($outstandingPrincipal === 0 && ! $loan->installments()->whereRaw('(principal_due + interest_due + fees_due + penalties_due) > (principal_paid + interest_paid + fees_paid + penalties_paid)')->exists()) {
                $loan->status = LoanStatus::Settled;
            }

            $loan->save();

            $receiver = User::query()->findOrFail($data->receivedBy);
            $this->cashTransactions->post(
                $receiver,
                'Loan Repayment',
                'In',
                $data->amount,
                $repayment,
                $data->paymentMethod === 'Cash',
                $loan->branch_id,
            );
            $mappings = $loan->application->productVersion->accountMappings->keyBy('purpose');
            $channelPurpose = match ($data->paymentMethod) {
                'Bank' => 'bank_account',
                'Mobile Money' => 'mobile_money_account',
                default => 'cash_account',
            };
            $this->accounting->post($receiver, "Loan repayment {$repayment->receipt_number}", Repayment::class, $repayment->id, [
                ['account_code' => $mappings->get($channelPurpose)?->account?->code ?? '1000', 'debit' => $data->amount],
                ['account_code' => $mappings->get('loan_receivable')?->account?->code ?? '1100', 'credit' => $this->fromMinorUnits($allocated['principal'])],
                ['account_code' => $mappings->get('interest_income')?->account?->code ?? '4000', 'credit' => $this->fromMinorUnits($allocated['interest'])],
                ['account_code' => $mappings->get('fee_income')?->account?->code ?? '4100', 'credit' => $this->fromMinorUnits($allocated['fees'])],
                ['account_code' => $mappings->get('penalty_income')?->account?->code ?? '4200', 'credit' => $this->fromMinorUnits($allocated['penalties'])],
            ], $loan->branch_id);

            return $repayment->load('allocations');
        }, 3);
    }

    /** @return array{penalties: int, fees: int, interest: int, principal: int} */
    private function allocate(Loan $loan, Repayment $repayment, int $available): array
    {
        $allocated = ['penalties' => 0, 'fees' => 0, 'interest' => 0, 'principal' => 0];
        $installments = $loan->installments()->lockForUpdate()->orderBy('due_date')->orderBy('installment_number')->get();

        foreach ($installments as $installment) {
            foreach (self::ALLOCATION_ORDER as $component) {
                $amount = min($available, $installment->outstandingFor($component));

                if ($amount === 0) {
                    continue;
                }

                $this->recordAllocation($repayment, $installment, $component, $amount);
                $allocated[$component] += $amount;
                $available -= $amount;

                if ($available === 0) {
                    return $allocated;
                }
            }
        }

        throw new DomainException('The repayment exceeds the total outstanding loan obligations.');
    }

    private function recordAllocation(Repayment $repayment, LoanInstallment $installment, string $component, int $amount): void
    {
        $installment->applyAllocation($component, $amount);
        $repayment->allocations()->create([
            'loan_installment_id' => $installment->id,
            'component' => $component,
            'amount' => $this->fromMinorUnits($amount),
        ]);
    }

    private function findIdempotentRepayment(RepaymentData $data): ?Repayment
    {
        if ($data->idempotencyKey === null) {
            return null;
        }

        return Repayment::query()->where('idempotency_key', $data->idempotencyKey)->first();
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
