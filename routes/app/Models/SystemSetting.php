<?php

namespace App\Models;

use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
