<?php

namespace App\Models;

use Database\Factories\StreetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Street extends Model
{
    /** @use HasFactory<StreetFactory> */
    use HasFactory;

    protected $fillable = ['ward_id', 'code', 'name'];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
