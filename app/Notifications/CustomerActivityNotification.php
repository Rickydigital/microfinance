<?php

namespace App\Notifications;

use App\Notifications\Channels\NextSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $subject, public readonly string $message, public readonly string $type = 'customer_activity') {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            'database',
            config('microfinance.notifications.email_enabled') && $notifiable->email ? 'mail' : null,
            config('microfinance.notifications.sms_enabled') && $notifiable->phone ? NextSmsChannel::class : null,
        ]));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Dear '.$notifiable->first_name.',')
            ->line($this->message)
            ->line('Please contact your branch if you need assistance.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }

    public function toNextSms(object $notifiable): string
    {
        return config('microfinance.institution_name').': '.$this->message;
    }
}
