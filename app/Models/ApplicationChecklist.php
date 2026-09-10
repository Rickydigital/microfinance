<?php

namespace App\Models;

use Database\Factories\ApplicationChecklistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationChecklist extends Model
{
    /** @use HasFactory<ApplicationChecklistFactory> */
    use HasFactory;

    protected $fillable = ['loan_application_id', 'code', 'label', 'is_required', 'is_completed', 'completed_by', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_completed' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
