<?php

namespace App\Services;

use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\NumberSequence;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class LoanDisbursementService
{
    public function __construct(
        private readonly LoanScheduleService $schedules,
        private readonly AccountingPostingService $accounting,
        private readonly CashTransactionService $cashTransactions,
    ) {}

    public function prepare(LoanApplication $application, User $creator, string $method, ?string $destinationReference = null): LoanDisbursement
    {
        return DB::transaction(function () use ($application, $creator, $method, $destinationReference): LoanDisbursement {
            $application = LoanApplication::query()->with(['productVersion.fees', 'checklistItems', 'guarantors.guarantor', 'collaterals.collateral'])->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== LoanApplicationStatus::Approved) {
                throw new DomainException('Only an approved application may be prepared for disbursement.');
            }

            if ($application->checklistItems->where('is_required', true)->contains('is_completed', false)) {
                throw new DomainException('All required pre-disbursement checklist items must be completed.');
            }

            $acceptedGuarantors = $application->guarantors->filter(fn ($link): bool => $link->status === 'Accepted' && $link->guarantor->status === 'Verified')->count();

            if ($acceptedGuarantors < $application->productVersion->required_guarantors) {
                throw new DomainException('The configured number of verified guarantors has not been met.');
            }

            if ($application->productVersion->requires_collateral && ! $application->collaterals->contains(fn ($link): bool => $link->status === 'Pledged' && $link->collateral->status === 'Verified')) {
                throw new DomainException('Verified pledged collateral is required before disbursement.');
            }

            $gross = (float) $application->approved_amount;
            $assurancePaid = (float) $application->paymentSubmissions()->where('purpose', 'Assurance')->where('status', 'Approved')->sum('amount');
            if ($assurancePaid < $application->assuranceAmount()) {
                throw new DomainException('The required assurance payment must be approved before disbursement.');
            }
            $fees = $application->productVersion->fees->where('collection_point', 'Disbursement')->where('is_financed', false)->sum(function ($fee) use ($gross): float {
                return $fee->calculation_method === 'Percentage' ? $gross * (float) $fee->value : (float) $fee->value;
            });

            $fees = round($fees, 2);
            if ($gross <= 0 || $fees >= $gross) {
                throw new DomainException('Deducted fees must be less than the loan principal.');
            }
            $application->update(['status' => LoanApplicationStatus::ReadyForDisbursement]);

            return LoanDisbursement::query()->create([
                'loan_application_id' => $application->id,
                'branch_id' => $application->branch_id,
                'disbursement_number' => NumberSequence::issue('disbursement'),
                'gross_amount' => number_format($gross, 2, '.', ''),
                'deducted_fees' => number_format($fees, 2, '.', ''),
                'net_amount' => number_format($gross - $fees, 2, '.', ''),
                'method' => $method,
                'destination_reference' => $destinationReference,
                'status' => 'Pending Authorization',
                'created_by' => $creator->id,
            ]);
        });
    }

    public function authorize(LoanDisbursement $disbursement, User $authorizer, CarbonImmutable $firstDueDate): Loan
    {
        return DB::transaction(function () use ($disbursement, $authorizer, $firstDueDate): Loan {
            $disbursement = LoanDisbursement::query()->lockForUpdate()->findOrFail($disbursement->id);

            if ($disbursement->status !== 'Pending Authorization' || $disbursement->created_by === $authorizer->id) {
                throw new DomainException('Disbursement requires authorization by a different employee.');
            }

            $application = LoanApplication::query()->with('productVersion.accountMappings.account')->lockForUpdate()->findOrFail($disbursement->loan_application_id);
            $loan = Loan::query()->create([
                'branch_id' => $application->branch_id,
                'customer_id' => $application->customer_id,
                'loan_application_id' => $application->id,
                'loan_number' => NumberSequence::issue('loan'),
                'principal_amount' => $disbursement->gross_amount,
                'outstanding_principal' => $disbursement->gross_amount,
                'arrears_amount' => 0,
                'days_past_due' => 0,
                'status' => LoanStatus::Active,
                'disbursed_at' => today(),
                'maturity_date' => $firstDueDate->addMonths(max(0, $application->requested_term - 1)),
            ]);

            $this->schedules->generate($loan, $firstDueDate);
            $loan->update(['maturity_date' => $loan->installments()->max('due_date')]);
            $creator = User::query()->findOrFail($disbursement->created_by);
            $this->cashTransactions->post($creator, 'Loan Disbursement', 'Out', $disbursement->net_amount, $disbursement, $disbursement->method === 'Cash');
            $mappings = $application->productVersion->accountMappings->keyBy('purpose');
            $channelPurpose = match ($disbursement->method) {
                'Bank' => 'bank_account',
                'Mobile Money' => 'mobile_money_account',
                default => 'cash_account',
            };
            $loanReceivable = $mappings->get('loan_receivable')?->account?->code ?? '1100';
            $fundingAccount = $mappings->get($channelPurpose)?->account?->code ?? '1000';
            $feeIncome = $mappings->get('fee_income')?->account?->code ?? '4100';
            $this->accounting->post($authorizer, "Loan disbursement {$loan->loan_number}", LoanDisbursement::class, $disbursement->id, [
                ['account_code' => $loanReceivable, 'debit' => $disbursement->gross_amount],
                ['account_code' => $fundingAccount, 'credit' => $disbursement->net_amount],
                ['account_code' => $feeIncome, 'credit' => $disbursement->deducted_fees],
            ], $application->branch_id);

            $disbursement->update(['loan_id' => $loan->id, 'status' => 'Disbursed', 'authorized_by' => $authorizer->id, 'authorized_at' => now(), 'disbursed_at' => now()]);
            $application->update(['status' => LoanApplicationStatus::ConvertedToLoan]);

            return $loan->load('installments');
        });
    }
}
