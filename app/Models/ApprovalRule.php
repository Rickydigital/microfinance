<?php

namespace App\Models;

use Database\Factories\ApprovalRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRule extends Model
{
    protected $fillable = [
        'loan_product_id',
        'branch_id',
        'minimum_amount',
        'maximum_amount',
        'risk_grade',
        'role_name',
        'sequence',
        'is_active',
    ];

    /** @use HasFactory<ApprovalRuleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['minimum_amount' => 'decimal:2', 'maximum_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loanApprovals(): HasMany
    {
        return $this->hasMany(LoanApproval::class);
    }
}
