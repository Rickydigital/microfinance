<?php

namespace App\Models;

use Database\Factories\LoanCollateralFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCollateral extends Model
{
    /** @use HasFactory<LoanCollateralFactory> */
    use HasFactory;

    protected $fillable = ['loan_application_id', 'collateral_id', 'pledged_value', 'status', 'released_at', 'release_reason'];

    protected function casts(): array
    {
        return ['pledged_value' => 'decimal:2', 'released_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function collateral(): BelongsTo
    {
        return $this->belongsTo(Collateral::class);
    }
}
