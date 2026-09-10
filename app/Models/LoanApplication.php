<?php

namespace App\Models;

use App\LoanApplicationStatus;
use Database\Factories\LoanApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanApplication extends Model
{
    /** @use HasFactory<LoanApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id', 'customer_id', 'loan_product_version_id', 'created_by', 'portal_submitted_by',
        'application_number', 'source', 'requested_amount', 'requested_term',
        'repayment_frequency', 'purpose', 'declared_monthly_income',
        'declared_monthly_expenses', 'existing_debt_payment', 'approved_amount',
        'status', 'submitted_at', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['status' => LoanApplicationStatus::class, 'requested_amount' => 'decimal:2', 'approved_amount' => 'decimal:2', 'submitted_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    public function assuranceAmount(): float
    {
        return round((float) ($this->approved_amount ?? $this->requested_amount) * (float) $this->productVersion->assurance_percent / 100, 2);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loan(): HasOne
    {
        return $this->hasOne(Loan::class);
    }

    public function productVersion(): BelongsTo
    {
        return $this->belongsTo(LoanProductVersion::class, 'loan_product_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function portalSubmitter(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'portal_submitted_by');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(LoanAssessment::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LoanApproval::class)->orderBy('sequence');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ApplicationChecklist::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }

    public function disbursement(): HasOne
    {
        return $this->hasOne(LoanDisbursement::class);
    }
}
