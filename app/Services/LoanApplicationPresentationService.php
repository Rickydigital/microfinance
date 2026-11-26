<?php

namespace App\Services;

use App\LoanApplicationStatus;
use App\Models\LoanApplication;

class LoanApplicationPresentationService
{
    /** @param array<string, mixed> $quote @param array<string, mixed> $kyc @return array<string, mixed> */
    public function build(LoanApplication $application, array $quote, array $kyc): array
    {
        $approvedBase = (float) ($application->approved_amount ?: $application->requested_amount);
        $guaranteedAmount = (float) $application->guarantors->sum('guaranteed_amount');
        $collateralValue = (float) $application->collaterals->sum('pledged_value');
        $customerExposure = (float) $application->customer->loans->whereIn('status.value', ['Active', 'In Arrears'])->sum('outstanding_principal');
        $currentStatus = $application->status;
        $workflowOrder = [
            LoanApplicationStatus::Draft,
            LoanApplicationStatus::Submitted,
            LoanApplicationStatus::UnderAssessment,
            LoanApplicationStatus::PendingApproval,
            LoanApplicationStatus::Approved,
            LoanApplicationStatus::ReadyForDisbursement,
            LoanApplicationStatus::ConvertedToLoan,
        ];
        $currentPosition = array_search($currentStatus, $workflowOrder, true);
        $workflow = collect($workflowOrder)->map(function (LoanApplicationStatus $status, int $position) use ($currentPosition, $currentStatus): array {
            return [
                'label' => $status->value,
                'state' => $currentStatus === LoanApplicationStatus::Rejected
                    ? ($position === 0 ? 'completed' : 'blocked')
                    : ($position < ($currentPosition === false ? 0 : $currentPosition) ? 'completed' : ($position === $currentPosition ? 'current' : 'pending')),
            ];
        })->all();

        $warnings = [];

        if (! $kyc['ready']) {
            $warnings[] = 'Customer KYC requirements are incomplete.';
        }

        if (! $quote['affordable']) {
            array_push($warnings, ...$quote['policy_failures']);
        }

        if ($application->guarantors->where('status', 'Verified')->count() < $application->productVersion->required_guarantors) {
            $warnings[] = 'The configured number of verified guarantors has not been met.';
        }

        if ($application->productVersion->requires_collateral && $application->collaterals->where('status', 'Verified')->isEmpty()) {
            $warnings[] = 'Verified collateral is required by this product.';
        }

        $timeline = collect([
            ['date' => $application->created_at, 'event' => 'Application created', 'actor' => $application->creator?->full_name, 'detail' => 'Draft application opened.'],
            ['date' => $application->submitted_at, 'event' => 'Application submitted', 'actor' => $application->creator?->full_name, 'detail' => 'Submitted for credit assessment.'],
        ]);
        foreach ($application->assessments as $assessment) {
            $timeline->push(['date' => $assessment->assessed_at, 'event' => 'Credit assessment', 'actor' => $assessment->assessor?->full_name, 'detail' => $assessment->recommendation.' · Risk '.$assessment->risk_grade]);
        }
        foreach ($application->approvals as $approval) {
            if ($approval->decided_at !== null) {
                $timeline->push(['date' => $approval->decided_at, 'event' => 'Approval level '.$approval->sequence, 'actor' => $approval->decisionMaker?->full_name, 'detail' => $approval->status.($approval->comments ? ' · '.$approval->comments : '')]);
            }
        }
        foreach ($application->checklistItems->where('is_completed', true) as $item) {
            $timeline->push(['date' => $item->completed_at, 'event' => 'Condition completed', 'actor' => $item->completedBy?->full_name, 'detail' => $item->label]);
        }
        if ($application->disbursement !== null) {
            $timeline->push(['date' => $application->disbursement->created_at, 'event' => 'Disbursement prepared', 'actor' => $application->disbursement->creator?->full_name, 'detail' => $application->disbursement->disbursement_number]);
            $timeline->push(['date' => $application->disbursement->authorized_at, 'event' => 'Disbursement authorized', 'actor' => $application->disbursement->authorizer?->full_name, 'detail' => $application->disbursement->status]);
        }

        $schedule = $application->loan?->installments->isNotEmpty()
            ? $application->loan->installments->map(fn ($item): array => ['number' => $item->installment_number, 'due_date' => $item->due_date?->format('d M Y'), 'opening_principal' => null, 'principal' => (float) $item->principal_due, 'interest' => (float) $item->interest_due, 'fees' => (float) $item->fees_due, 'installment' => (float) $item->principal_due + (float) $item->interest_due + (float) $item->fees_due + (float) $item->penalties_due, 'closing_principal' => null, 'paid' => (float) $item->principal_paid + (float) $item->interest_paid + (float) $item->fees_paid + (float) $item->penalties_paid, 'status' => $item->status])->all()
            : collect($quote['projected_schedule'])->map(fn (array $item): array => $item + ['due_date' => null, 'paid' => 0, 'status' => 'Projected'])->all();

        return [
            'workflow' => $workflow,
            'warnings' => $warnings,
            'guaranteed_amount' => $guaranteedAmount,
            'guarantee_coverage' => $approvedBase > 0 ? ($guaranteedAmount / $approvedBase) * 100 : 0,
            'collateral_value' => $collateralValue,
            'collateral_coverage' => $approvedBase > 0 ? ($collateralValue / $approvedBase) * 100 : 0,
            'customer_exposure' => $customerExposure,
            'other_open_applications' => $application->customer->loanApplications->where('id', '!=', $application->id)->whereNotIn('status', [LoanApplicationStatus::Rejected, LoanApplicationStatus::ConvertedToLoan])->count(),
            'schedule' => $schedule,
            'timeline' => $timeline->filter(fn (array $event): bool => $event['date'] !== null)->sortBy('date')->values()->all(),
        ];
    }
}
