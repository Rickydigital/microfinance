<?php

namespace App\Models;

use Database\Factories\LoanProductAccountMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanProductAccountMapping extends Model
{
    /** @use HasFactory<LoanProductAccountMappingFactory> */
    use HasFactory;

    protected $fillable = ['loan_product_version_id', 'purpose', 'chart_of_account_id'];

    public function productVersion(): BelongsTo
    {
        return $this->belongsTo(LoanProductVersion::class, 'loan_product_version_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
