<?php

namespace App\Models;

use Database\Factories\TillSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TillSession extends Model
{
    /** @use HasFactory<TillSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id', 'user_id', 'opening_balance', 'expected_balance',
        'actual_balance', 'difference_amount', 'difference_reason',
        'difference_authorized_by', 'difference_authorized_at', 'status', 'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'expected_balance' => 'decimal:2', 'actual_balance' => 'decimal:2', 'difference_amount' => 'decimal:2', 'difference_authorized_at' => 'datetime', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function differenceAuthorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'difference_authorized_by');
    }
}
