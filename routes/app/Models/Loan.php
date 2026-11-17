<?php

namespace App\Models;

use App\LoanStatus;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id', 'customer_id', 'loan_application_id', 'loan_number',
        'principal_amount', 'outstanding_principal', 'arrears_amount',
        'days_past_due', 'status', 'disbursed_at', 'maturity_date',
        'imported_at', 'imported_by', 'historical_net_disbursement', 'historical_upfront_income',
    ];

    protected function casts(): array
    {
        return ['status' => LoanStatus::class, 'principal_amount' => 'decimal:2', 'outstanding_principal' => 'decimal:2', 'arrears_amount' => 'decimal:2', 'disbursed_at' => 'date', 'maturity_date' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoanTransaction::class);
    }

    public function collectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class);
    }

    public function disbursement(): HasOne
    {
        return $this->hasOne(LoanDisbursement::class);
    }

    public function penaltyCharges(): HasMany
    {
        return $this->hasMany(LoanPenaltyCharge::class);
    }
}
