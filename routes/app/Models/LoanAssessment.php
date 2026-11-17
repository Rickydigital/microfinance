<?php

namespace App\Models;

use Database\Factories\LoanAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAssessment extends Model
{
    /** @use HasFactory<LoanAssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'loan_application_id', 'assessed_by', 'verified_monthly_income', 'verified_monthly_expenses',
        'existing_debt_payment', 'disposable_income', 'proposed_installment', 'installment_ratio',
        'risk_grade', 'recommendation', 'recommended_amount', 'conditions', 'field_visit_notes',
        'rationale', 'exception_flags', 'assessed_at',
    ];

    protected function casts(): array
    {
        return ['verified_monthly_income' => 'decimal:2', 'verified_monthly_expenses' => 'decimal:2', 'existing_debt_payment' => 'decimal:2', 'disposable_income' => 'decimal:2', 'proposed_installment' => 'decimal:2', 'installment_ratio' => 'decimal:4', 'recommended_amount' => 'decimal:2', 'exception_flags' => 'array', 'assessed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
