<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $subject, public readonly string $message, public readonly string $type = 'workflow') {}

    public function via(object $notifiable): array
    {
        return array_values(array_filter(['database', config('microfinance.notifications.email_enabled') && $notifiable->email ? 'mail' : null]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subject)->greeting('Dear '.$notifiable->first_name.',')->line($this->message)->action('Open the approval queue', url('/loan-approvals'));
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return ['type' => $this->type, 'subject' => $this->subject, 'message' => $this->message];
    }
}
