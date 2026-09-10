<?php

namespace App\Models;

use Database\Factories\LoanPenaltyChargeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenaltyCharge extends Model
{
    /** @use HasFactory<LoanPenaltyChargeFactory> */
    use HasFactory;

    protected $fillable = ['loan_id', 'loan_installment_id', 'product_penalty_rule_id', 'charge_date', 'calculation_base', 'amount', 'status', 'journal_entry_id'];

    protected function casts(): array
    {
        return ['charge_date' => 'date', 'calculation_base' => 'decimal:2', 'amount' => 'decimal:2'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class, 'loan_installment_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ProductPenaltyRule::class, 'product_penalty_rule_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
