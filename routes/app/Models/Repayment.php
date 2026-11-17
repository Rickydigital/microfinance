<?php

namespace App\Models;

use Database\Factories\RepaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repayment extends Model
{
    /** @use HasFactory<RepaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'loan_id', 'receipt_number', 'amount', 'status', 'payment_method',
        'external_reference', 'paid_at', 'received_by', 'reversed_repayment_id',
        'reversal_reason', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RepaymentAllocation::class)->orderBy('id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function reversedRepayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_repayment_id');
    }
}
