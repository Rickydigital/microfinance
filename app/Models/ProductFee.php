<?php

namespace App\Models;

use Database\Factories\ProductFeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFee extends Model
{
    protected $fillable = [
        'loan_product_version_id',
        'name',
        'calculation_method',
        'value',
        'collection_point',
        'is_financed',
    ];

    /** @use HasFactory<ProductFeeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['value' => 'decimal:6', 'is_financed' => 'boolean'];
    }

    public function productVersion(): BelongsTo
    {
        return $this->belongsTo(LoanProductVersion::class, 'loan_product_version_id');
    }
}
