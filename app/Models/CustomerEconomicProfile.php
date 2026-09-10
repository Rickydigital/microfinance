<?php

namespace App\Models;

use Database\Factories\CustomerEconomicProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerEconomicProfile extends Model
{
    protected $fillable = [
        'customer_id',
        'occupation',
        'employer_or_business',
        'monthly_income',
        'monthly_expenses',
        'existing_debt_payment',
        'business_turnover',
        'income_source',
        'effective_from',
        'effective_to',
    ];

    /** @use HasFactory<CustomerEconomicProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['monthly_income' => 'decimal:2', 'monthly_expenses' => 'decimal:2', 'existing_debt_payment' => 'decimal:2', 'business_turnover' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
