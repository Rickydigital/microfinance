<?php

namespace App\Models;

use App\CustomerStatus;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'branch_id', 'assigned_officer_id', 'customer_number', 'type', 'first_name',
        'middle_name', 'last_name', 'gender', 'date_of_birth', 'phone', 'email',
        'identification_type', 'identification_number', 'status', 'kyc_risk_rating',
        'verified_at',
        'password', 'must_change_password', 'remember_token', 'portal_registered_at', 'portal_last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function routeNotificationForNextsms(): ?string
    {
        return $this->phone;
    }

    protected function casts(): array
    {
        return ['status' => CustomerStatus::class, 'date_of_birth' => 'date', 'verified_at' => 'datetime', 'password' => 'hashed', 'must_change_password' => 'boolean', 'portal_registered_at' => 'datetime', 'portal_last_login_at' => 'datetime'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function identifications(): HasMany
    {
        return $this->hasMany(CustomerIdentification::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function economicProfiles(): HasMany
    {
        return $this->hasMany(CustomerEconomicProfile::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(CustomerRelationship::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function registeredGuarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class, 'principal_customer_id');
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(Collateral::class);
    }
}
