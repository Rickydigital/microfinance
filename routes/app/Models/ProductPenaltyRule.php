<?php

namespace App\Models;

use Database\Factories\ProductPenaltyRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPenaltyRule extends Model
{
    /** @use HasFactory<ProductPenaltyRuleFactory> */
    use HasFactory;

    protected $fillable = ['loan_product_version_id', 'name', 'calculation_method', 'calculation_basis', 'value', 'grace_days', 'frequency', 'maximum_amount', 'is_active'];

    protected function casts(): array
    {
        return ['value' => 'decimal:6', 'maximum_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function productVersion(): BelongsTo
    {
        return $this->belongsTo(LoanProductVersion::class, 'loan_product_version_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(LoanPenaltyCharge::class);
    }
}
