<?php

namespace App\Models;

use Database\Factories\LoanProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanProduct extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    /** @use HasFactory<LoanProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LoanProductVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(LoanProductVersion::class)->latestOfMany('version');
    }

    public function approvalRules(): HasMany
    {
        return $this->hasMany(ApprovalRule::class);
    }
}
