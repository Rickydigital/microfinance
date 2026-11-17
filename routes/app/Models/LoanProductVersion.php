<?php

namespace App\Models;

use Database\Factories\LoanProductVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProductVersion extends Model
{
    protected $attributes = ['assurance_percent' => 3];

    protected $fillable = [
        'loan_product_id',
        'version',
        'minimum_amount',
        'maximum_amount',
        'minimum_term',
        'maximum_term',
        'term_unit',
        'interest_method',
        'interest_rate',
        'assurance_percent',
        'rate_basis',
        'repayment_frequency',
        'principal_grace_period',
        'interest_grace_period',
        'required_guarantors',
        'requires_collateral',
        'maximum_installment_ratio',
        'effective_from',
        'effective_to',
        'created_by',
    ];

    /** @use HasFactory<LoanProductVersionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['assurance_percent' => 'decimal:4', 'minimum_amount' => 'decimal:2', 'maximum_amount' => 'decimal:2', 'interest_rate' => 'decimal:6', 'maximum_installment_ratio' => 'decimal:4', 'requires_collateral' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(ProductFee::class);
    }

    public function penaltyRules(): HasMany
    {
        return $this->hasMany(ProductPenaltyRule::class);
    }

    public function accountMappings(): HasMany
    {
        return $this->hasMany(LoanProductAccountMapping::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class, 'loan_product_version_id');
    }
}
