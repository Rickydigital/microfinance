<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected $fillable = [
        'institution_id', 'code', 'name', 'phone', 'email', 'address', 'is_active',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function tillSessions(): HasMany
    {
        return $this->hasMany(TillSession::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
