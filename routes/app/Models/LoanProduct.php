<?php

namespace App\Models;

use Database\Factories\LoanProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function approvalRules(): HasMany
    {
        return $this->hasMany(ApprovalRule::class);
    }
}
