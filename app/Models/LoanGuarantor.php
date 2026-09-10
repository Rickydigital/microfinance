<?php

namespace App\Models;

use Database\Factories\LoanGuarantorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanGuarantor extends Model
{
    /** @use HasFactory<LoanGuarantorFactory> */
    use HasFactory;

    protected $fillable = ['loan_application_id', 'guarantor_id', 'guaranteed_amount', 'status', 'consent_received', 'consented_at', 'notes'];

    protected function casts(): array
    {
        return ['guaranteed_amount' => 'decimal:2', 'consent_received' => 'boolean', 'consented_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }
}
