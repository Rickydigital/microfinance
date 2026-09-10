<?php

namespace App\Models;

use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'customer_type_code', 'is_required_for_kyc', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_required_for_kyc' => 'boolean', 'is_active' => 'boolean'];
    }
}
