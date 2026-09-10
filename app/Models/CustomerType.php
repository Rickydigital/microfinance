<?php

namespace App\Models;

use Database\Factories\CustomerTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    /** @use HasFactory<CustomerTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_legal_entity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_legal_entity' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
