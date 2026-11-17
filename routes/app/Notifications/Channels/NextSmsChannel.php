<?php

namespace App\Notifications\Channels;

use App\Services\NextSmsService;
use Illuminate\Notifications\Notification;

class NextSmsChannel
{
    public function __construct(private readonly NextSmsService $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toNextSms')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('nextsms', $notification);

        if ($phone) {
            $this->sms->send($phone, $notification->toNextSms($notifiable), $notifiable);
        }
    }
}
