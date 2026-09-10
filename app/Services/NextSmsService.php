<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class NextSmsService
{
    public function send(string $phone, string $message, ?Model $smsable = null): SmsLog
    {
        $formattedPhone = $this->formatPhone($phone);
        $reference = 'MFI-'.strtoupper(Str::random(12));
        $log = SmsLog::query()->create([
            'smsable_type' => $smsable?->getMorphClass(),
            'smsable_id' => $smsable?->getKey(),
            'phone' => $formattedPhone ?? $phone,
            'message' => $message,
            'status' => 'Pending',
            'local_reference' => $reference,
        ]);

        if ($formattedPhone === null) {
            $log->update(['status' => 'Failed', 'failure_reason' => 'Invalid Tanzanian phone number.']);

            return $log;
        }

        try {
            $response = Http::withBasicAuth((string) config('services.nextsms.username'), (string) config('services.nextsms.password'))
                ->acceptJson()
                ->timeout((int) config('services.nextsms.timeout', 30))
                ->post($this->endpoint(), [
                    'from' => config('services.nextsms.sender_id'),
                    'to' => [$formattedPhone],
                    'text' => $message,
                    'reference' => $reference,
                ]);

            $body = $response->json() ?? [];
            $providerMessage = $body['messages'][0] ?? [];
            $log->update([
                'status' => $response->successful() ? 'Accepted' : 'Failed',
                'provider_message_id' => $providerMessage['messageId'] ?? null,
                'provider_send_reference' => $providerMessage['sendReference'] ?? null,
                'provider_response' => $body,
                'failure_reason' => $response->successful() ? null : $response->body(),
                'sent_at' => $response->successful() ? now() : null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $log->update(['status' => 'Failed', 'failure_reason' => $exception->getMessage()]);
        }

        return $log->refresh();
    }

    private function endpoint(): string
    {
        $path = config('services.nextsms.test_mode') ? '/api/sms/v1/test/text/single' : '/api/sms/v1/text/single';

        return rtrim((string) config('services.nextsms.base_url'), '/').$path;
    }

    private function formatPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '255') && strlen($digits) === 12) {
            return $digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && in_array($digits[0], ['6', '7'], true)) {
            return '255'.$digits;
        }

        return null;
    }
}
