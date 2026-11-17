<?php

namespace App\Models;

use Database\Factories\GuarantorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guarantor extends Model
{
    /** @use HasFactory<GuarantorFactory> */
    use HasFactory;

    protected $fillable = ['customer_id', 'principal_customer_id', 'first_name', 'middle_name', 'last_name', 'phone', 'identification_type', 'identification_number', 'occupation', 'monthly_income', 'status', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return ['monthly_income' => 'decimal:2', 'verified_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function principalCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'principal_customer_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function guarantees(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }
}
