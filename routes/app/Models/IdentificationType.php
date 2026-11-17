<?php

namespace App\Models;

use Database\Factories\IdentificationTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentificationType extends Model
{
    /** @use HasFactory<IdentificationTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'customer_type_code',
        'number_pattern',
        'pattern_hint',
        'requires_expiry',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_expiry' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
