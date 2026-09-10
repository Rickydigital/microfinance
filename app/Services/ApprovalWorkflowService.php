<?php

namespace App\Services;

use App\LoanApplicationStatus;
use App\Models\ApprovalRule;
use App\Models\LoanApplication;
use App\Models\LoanApproval;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    public function initialize(LoanApplication $application): void
    {
        DB::transaction(function () use ($application): void {
            $application = LoanApplication::query()->lockForUpdate()->findOrFail($application->id);
            $assessment = $application->assessments()->latest('assessed_at')->first();
            $amount = (float) ($assessment?->recommended_amount ?? $application->requested_amount);
            $rules = ApprovalRule::query()->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('loan_product_id')->orWhere('loan_product_id', $application->productVersion->loan_product_id))
                ->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $application->branch_id))
                ->where('minimum_amount', '<=', $amount)
                ->where(fn ($query) => $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $amount))
                ->where(fn ($query) => $query->whereNull('risk_grade')->orWhere('risk_grade', $assessment?->risk_grade))
                ->orderBy('sequence')->get()
                ->sortByDesc(fn (ApprovalRule $rule): int => ($rule->loan_product_id !== null ? 2 : 0) + ($rule->branch_id !== null ? 1 : 0))
                ->unique('sequence')
                ->sortBy('sequence');

            if ($rules->isEmpty()) {
                throw new DomainException('No approval route is configured for this application amount.');
            }

            foreach ($rules as $rule) {
                $application->approvals()->updateOrCreate(
                    ['sequence' => $rule->sequence],
                    ['approval_rule_id' => $rule->id, 'required_role' => $rule->role_name, 'status' => 'Pending'],
                );
            }

            $application->update(['status' => LoanApplicationStatus::PendingApproval]);
        });
    }

    public function decide(LoanApproval $approval, User $decisionMaker, string $decision, ?string $comments = null, ?string $conditions = null): LoanApproval
    {
        return DB::transaction(function () use ($approval, $decisionMaker, $decision, $comments, $conditions): LoanApproval {
            $approval = LoanApproval::query()->lockForUpdate()->findOrFail($approval->id);
            $application = LoanApplication::query()->lockForUpdate()->findOrFail($approval->loan_application_id);

            $canApproveLevel = $decisionMaker->hasRole($approval->required_role) || $decisionMaker->hasRole('System Administrator');

            if ($approval->status !== 'Pending' || ! $canApproveLevel) {
                throw new DomainException('This approval is not pending for the current user role.');
            }

            if ($application->created_by === $decisionMaker->id) {
                throw new DomainException('The application maker cannot approve their own application.');
            }

            $earlierPending = $application->approvals()->where('sequence', '<', $approval->sequence)->where('status', '!=', 'Approved')->exists();

            if ($earlierPending) {
                throw new DomainException('Earlier approval levels must decide first.');
            }

            $recommendedAmount = $application->assessments()->latest('assessed_at')->value('recommended_amount');
            $approval->update(['status' => $decision, 'decided_by' => $decisionMaker->id, 'approved_amount' => $decision === 'Approved' ? $recommendedAmount ?? $application->requested_amount : null, 'conditions' => $conditions, 'comments' => $comments, 'decided_at' => now()]);

            if ($decision === 'Rejected') {
                $application->update(['status' => LoanApplicationStatus::Rejected, 'decided_at' => now()]);
            } elseif ($decision === 'Returned') {
                $application->update(['status' => LoanApplicationStatus::UnderAssessment]);
            } elseif (! $application->approvals()->where('status', '!=', 'Approved')->exists()) {
                $approvedAmount = $application->approvals()->where('status', 'Approved')->min('approved_amount');
                $application->update(['status' => LoanApplicationStatus::Approved, 'approved_amount' => $approvedAmount, 'decided_at' => now()]);

                foreach ([
                    'customer_id_verified' => 'Customer identification verified',
                    'loan_agreement_signed' => 'Loan agreement signed',
                    'guarantor_agreement_signed' => 'Guarantor agreement signed',
                    'security_verified' => 'Required guarantor and collateral verified',
                    'documents_complete' => 'Required documents attached',
                ] as $code => $label) {
                    $application->checklistItems()->firstOrCreate(['code' => $code], ['label' => $label, 'is_required' => true]);
                }
            }

            return $approval->refresh();
        });
    }
}
