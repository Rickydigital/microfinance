<?php

namespace App\Services;

use App\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\LoanAssessment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreditAssessmentService
{
    /** @param array{monthly_income: numeric-string, monthly_expenses: numeric-string, existing_debt_payment: numeric-string, proposed_installment: numeric-string, risk_grade: string, recommendation: string, recommended_amount?: numeric-string|null, conditions?: string|null, field_visit_notes?: string|null, rationale: string} $data */
    public function assess(LoanApplication $application, User $assessor, array $data): LoanAssessment
    {
        return DB::transaction(function () use ($application, $assessor, $data): LoanAssessment {
            $application = LoanApplication::query()->lockForUpdate()->findOrFail($application->id);

            if (! in_array($application->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderAssessment], true)) {
                throw new DomainException('Only submitted applications may be assessed.');
            }

            $income = (float) $data['monthly_income'];
            $expenses = (float) $data['monthly_expenses'];
            $debt = (float) $data['existing_debt_payment'];
            $installment = (float) $data['proposed_installment'];
            $disposableIncome = $income - $expenses - $debt;
            $ratio = $disposableIncome > 0 ? $installment / $disposableIncome : 999;
            $maximumRatio = (float) $application->productVersion->maximum_installment_ratio;
            $exceptions = [];

            if (isset($data['recommended_amount']) && (float) $data['recommended_amount'] > (float) $application->requested_amount) {
                throw new DomainException('Recommended amount cannot exceed the requested amount.');
            }

            if ($disposableIncome <= 0) {
                $exceptions[] = 'NO_DISPOSABLE_INCOME';
            }

            if ($ratio > $maximumRatio) {
                $exceptions[] = 'INSTALLMENT_RATIO_EXCEEDED';
            }

            $assessment = $application->assessments()->create([
                'assessed_by' => $assessor->id,
                'verified_monthly_income' => $data['monthly_income'],
                'verified_monthly_expenses' => $data['monthly_expenses'],
                'existing_debt_payment' => $data['existing_debt_payment'],
                'disposable_income' => number_format($disposableIncome, 2, '.', ''),
                'proposed_installment' => $data['proposed_installment'],
                'installment_ratio' => number_format($ratio, 4, '.', ''),
                'risk_grade' => $data['risk_grade'],
                'recommendation' => $data['recommendation'],
                'recommended_amount' => $data['recommended_amount'] ?? null,
                'conditions' => $data['conditions'] ?? null,
                'field_visit_notes' => $data['field_visit_notes'] ?? null,
                'rationale' => $data['rationale'],
                'exception_flags' => $exceptions,
                'assessed_at' => now(),
            ]);

            $application->update(['status' => LoanApplicationStatus::Recommended]);

            return $assessment;
        });
    }
}
