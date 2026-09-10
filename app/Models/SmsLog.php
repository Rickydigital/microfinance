<?php

namespace App\Models;

use Database\Factories\SmsLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsLog extends Model
{
    /** @use HasFactory<SmsLogFactory> */
    use HasFactory;

    protected $fillable = [
        'smsable_type', 'smsable_id', 'phone', 'message', 'status', 'local_reference',
        'provider_message_id', 'provider_send_reference', 'provider_response',
        'failure_reason', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['provider_response' => 'array', 'sent_at' => 'datetime'];
    }

    public function smsable(): MorphTo
    {
        return $this->morphTo();
    }
}
