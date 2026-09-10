<?php

namespace App\Services;

use App\Data\RepaymentData;
use App\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\NumberSequence;
use App\Models\PaymentSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentSubmissionService
{
    public function __construct(private readonly RepaymentPostingService $repayments, private readonly AccountingPostingService $accounting, private readonly CashTransactionService $cash) {}

    public function review(PaymentSubmission $submission, User $reviewer, string $decision, ?string $notes): void
    {
        abort_unless($reviewer->can('repayment.approve'), 403);
        DB::transaction(function () use ($submission, $reviewer, $decision, $notes): void {
            $submission = PaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $application = LoanApplication::query()->with('productVersion.accountMappings.account')->lockForUpdate()->findOrFail($submission->loan_application_id);
            abort_if($reviewer->branch_id && $reviewer->branch_id !== $application->branch_id, 404);
            if ($submission->status !== 'Pending') {
                throw new DomainException('This payment has already been reviewed.');
            }
            if (! in_array($decision, ['Approved', 'Rejected'], true)) {
                throw new DomainException('Invalid review decision.');
            }
            if ($decision === 'Approved') {
                if ($submission->purpose === 'Repayment') {
                    $repayment = $this->repayments->post(new RepaymentData(
                        loanId: $submission->loan_id,
                        receivedBy: $reviewer->id,
                        receiptNumber: NumberSequence::issue('repayment'),
                        transactionNumber: NumberSequence::issue('transaction'),
                        amount: $submission->amount,
                        paymentMethod: $submission->payment_method,
                        paidAt: CarbonImmutable::parse($submission->paid_at),
                        externalReference: $submission->external_reference,
                        idempotencyKey: 'payment-submission-'.$submission->id,
                    ));
                    $submission->repayment_id = $repayment->id;
                } else {
                    $paid = (float) $application->paymentSubmissions()->where('purpose', 'Assurance')->where('status', 'Approved')->sum('amount');
                    if ($application->status !== LoanApplicationStatus::Approved || round($paid + (float) $submission->amount, 2) > $application->assuranceAmount()) {
                        throw new DomainException('Assurance must belong to an approved application and cannot exceed the amount required.');
                    }
                    $mappings = $application->productVersion->accountMappings->keyBy('purpose');
                    $purpose = match ($submission->payment_method) {
                        'Bank' => 'bank_account',
                        'Mobile Money' => 'mobile_money_account',
                        default => 'cash_account',
                    };
                    $this->accounting->post($reviewer, 'Assurance payment '.$application->application_number, PaymentSubmission::class, $submission->id, [
                        ['account_code' => $mappings->get($purpose)?->account?->code ?? '1000', 'debit' => $submission->amount],
                        ['account_code' => $mappings->get('fee_income')?->account?->code ?? '4100', 'credit' => $submission->amount],
                    ], $application->branch_id);
                    $this->cash->post($reviewer, 'Loan Assurance', 'In', $submission->amount, $submission, $submission->payment_method === 'Cash', $application->branch_id);
                }
            }
            $submission->fill(['status' => $decision, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes])->save();
        }, 3);
    }
}
