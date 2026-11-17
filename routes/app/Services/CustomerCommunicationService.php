<?php

namespace App\Services;

use App\Models\Customer;
use App\Notifications\CustomerActivityNotification;

class CustomerCommunicationService
{
    public function customerRegistered(Customer $customer): void
    {
        $this->send($customer, 'Welcome to '.config('microfinance.institution_name'), "Your customer profile {$customer->customer_number} has been registered.", 'customer_registered');
    }

    public function applicationSubmitted(Customer $customer, string $applicationNumber): void
    {
        $this->send($customer, 'Loan application submitted', "Your loan application {$applicationNumber} has been submitted for assessment.", 'application_submitted');
    }

    public function repaymentReceived(Customer $customer, string $loanNumber, string $receiptNumber, string $amount): void
    {
        $currency = config('microfinance.currency');
        $this->send($customer, 'Payment received', "We received {$currency} {$amount} for loan {$loanNumber}. Receipt: {$receiptNumber}.", 'repayment_received');
    }

    public function assessmentCompleted(Customer $customer, string $applicationNumber, string $recommendation, float $recommendedAmount): void
    {
        $this->send($customer, 'Loan assessment completed', "Assessment for {$applicationNumber}: {$recommendation}. Recommended amount: ".config('microfinance.currency').' '.number_format($recommendedAmount, 2).'. Final approval is still required.', 'assessment_completed');
    }

    public function approvalDecision(Customer $customer, string $applicationNumber, string $decision, ?float $approvedAmount = null, ?float $fees = null, ?float $netProceeds = null): void
    {
        $message = "Loan application {$applicationNumber} decision: {$decision}.";
        if ($approvedAmount !== null) {
            $currency = config('microfinance.currency');
            $message .= " Approved amount: {$currency} ".number_format($approvedAmount, 2).". Fees: {$currency} ".number_format((float) $fees, 2).". Expected amount you receive: {$currency} ".number_format((float) $netProceeds, 2).'.';
        }
        $this->send($customer, 'Loan application decision', $message, 'approval_decision');
    }

    public function disbursementPrepared(Customer $customer, string $applicationNumber, float $grossAmount, float $fees, float $netAmount): void
    {
        $currency = config('microfinance.currency');
        $this->send($customer, 'Loan disbursement prepared', "Application {$applicationNumber}: approved loan {$currency} ".number_format($grossAmount, 2).", deducted processing fees {$currency} ".number_format($fees, 2).", amount to receive {$currency} ".number_format($netAmount, 2).'. Awaiting authorization.', 'disbursement_prepared');
    }

    public function loanDisbursed(Customer $customer, string $loanNumber, float $netAmount): void
    {
        $this->send($customer, 'Loan disbursed', 'Loan '.$loanNumber.' has been disbursed. Amount sent: '.config('microfinance.currency').' '.number_format($netAmount, 2).'.', 'loan_disbursed');
    }

    public function penaltyAdded(Customer $customer, string $loanNumber, float $amount, string $reason): void
    {
        $this->send($customer, 'Loan penalty applied', 'A penalty of '.config('microfinance.currency').' '.number_format($amount, 2)." was added to loan {$loanNumber}. Reason: {$reason}.", 'penalty_added');
    }

    public function portalPasswordReset(Customer $customer, string $temporaryPassword): void
    {
        $this->send($customer, 'Customer portal temporary password', 'Your temporary portal password is '.$temporaryPassword.'. Sign in and change it immediately. Do not share this password.', 'portal_password_reset');
    }

    public function collectionFollowUp(Customer $customer, string $message): void
    {
        $this->send($customer, 'Loan account follow-up', $message, 'collection_follow_up');
    }

    public function send(Customer $customer, string $subject, string $message, string $type): void
    {
        $customer->notify(new CustomerActivityNotification($subject, $message, $type));
    }
}
