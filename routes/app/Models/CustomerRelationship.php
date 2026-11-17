<?php

namespace App\Models;

use Database\Factories\CustomerRelationshipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRelationship extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'full_name',
        'phone',
        'identification_number',
        'address',
    ];

    /** @use HasFactory<CustomerRelationshipFactory> */
    use HasFactory;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
