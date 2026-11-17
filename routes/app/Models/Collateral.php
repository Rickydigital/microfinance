<?php

namespace App\Models;

use Database\Factories\CollateralFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collateral extends Model
{
    /** @use HasFactory<CollateralFactory> */
    use HasFactory;

    protected $fillable = ['customer_id', 'branch_id', 'collateral_number', 'type', 'description', 'ownership_reference', 'market_value', 'forced_sale_value', 'valuer', 'valuation_date', 'status', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return ['market_value' => 'decimal:2', 'forced_sale_value' => 'decimal:2', 'valuation_date' => 'date', 'verified_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(LoanCollateral::class);
    }
}
