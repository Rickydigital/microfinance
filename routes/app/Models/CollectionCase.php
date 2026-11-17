<?php

namespace App\Models;

use Database\Factories\CollectionCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionCase extends Model
{
    protected $fillable = [
        'loan_id',
        'assigned_to',
        'case_number',
        'status',
        'promise_to_pay_date',
        'promise_to_pay_amount',
        'last_action_at',
    ];

    /** @use HasFactory<CollectionCaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['promise_to_pay_date' => 'date', 'promise_to_pay_amount' => 'decimal:2', 'last_action_at' => 'datetime'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(CollectionAction::class);
    }
}
