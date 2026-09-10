<?php

namespace App\Models;

use Database\Factories\LoanApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApproval extends Model
{
    /** @use HasFactory<LoanApprovalFactory> */
    use HasFactory;

    protected $fillable = ['loan_application_id', 'approval_rule_id', 'decided_by', 'sequence', 'required_role', 'status', 'approved_amount', 'conditions', 'comments', 'decided_at'];

    protected function casts(): array
    {
        return ['approved_amount' => 'decimal:2', 'decided_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class, 'approval_rule_id');
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
