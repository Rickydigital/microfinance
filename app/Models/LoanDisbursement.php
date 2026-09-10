<?php

namespace App\Models;

use Database\Factories\LoanDisbursementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDisbursement extends Model
{
    /** @use HasFactory<LoanDisbursementFactory> */
    use HasFactory;

    protected $fillable = ['loan_application_id', 'loan_id', 'branch_id', 'disbursement_number', 'gross_amount', 'deducted_fees', 'net_amount', 'method', 'destination_reference', 'provider_reference', 'status', 'created_by', 'authorized_by', 'authorized_at', 'disbursed_at'];

    protected function casts(): array
    {
        return ['gross_amount' => 'decimal:2', 'deducted_fees' => 'decimal:2', 'net_amount' => 'decimal:2', 'authorized_at' => 'datetime', 'disbursed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
