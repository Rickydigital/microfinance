<?php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'region_id',
        'district_id',
        'ward_id',
        'street_id',
        'region',
        'district',
        'ward',
        'street',
        'description',
        'is_primary',
        'verified_by',
        'verified_at',
    ];

    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function regionRecord(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function districtRecord(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function wardRecord(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }

    public function streetRecord(): BelongsTo
    {
        return $this->belongsTo(Street::class, 'street_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
