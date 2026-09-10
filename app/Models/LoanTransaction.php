<?php

namespace App\Models;

use App\TransactionStatus;
use Database\Factories\LoanTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanTransaction extends Model
{
    protected $fillable = [
        'loan_id',
        'repayment_id',
        'created_by',
        'transaction_number',
        'type',
        'status',
        'amount',
        'principal_amount',
        'interest_amount',
        'fee_amount',
        'penalty_amount',
        'reverses_transaction_id',
        'reason',
        'posted_at',
    ];

    /** @use HasFactory<LoanTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => TransactionStatus::class, 'amount' => 'decimal:2', 'principal_amount' => 'decimal:2', 'interest_amount' => 'decimal:2', 'fee_amount' => 'decimal:2', 'penalty_amount' => 'decimal:2', 'posted_at' => 'datetime'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function repayment(): BelongsTo
    {
        return $this->belongsTo(Repayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_transaction_id');
    }
}
