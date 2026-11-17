<?php

namespace App\Models;

use Database\Factories\CollectionActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionAction extends Model
{
    protected $fillable = [
        'collection_case_id',
        'performed_by',
        'type',
        'result',
        'promise_to_pay_date',
        'promise_to_pay_amount',
        'notes',
        'performed_at',
    ];

    /** @use HasFactory<CollectionActionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['promise_to_pay_date' => 'date', 'promise_to_pay_amount' => 'decimal:2', 'performed_at' => 'datetime'];
    }

    public function collectionCase(): BelongsTo
    {
        return $this->belongsTo(CollectionCase::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
