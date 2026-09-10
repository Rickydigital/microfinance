<?php

namespace App\Models;

use Database\Factories\CustomerIdentificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerIdentification extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'number',
        'issuing_country',
        'issued_at',
        'expires_at',
        'verified_by',
        'verified_at',
    ];

    /** @use HasFactory<CustomerIdentificationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date', 'verified_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
