<?php

namespace App\Notifications;

use App\Models\LoanInstallment;
use App\Notifications\Channels\NextSmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanPaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly LoanInstallment $installment) {}

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
            ->subject('Loan payment reminder - '.$this->installment->loan->loan_number)
            ->greeting('Dear '.$notifiable->first_name.',')
            ->line($this->message())
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
            'type' => 'loan_payment_reminder',
            'loan_id' => $this->installment->loan_id,
            'loan_number' => $this->installment->loan->loan_number,
            'installment_id' => $this->installment->id,
            'due_date' => $this->installment->due_date->toDateString(),
            'amount_due' => $this->amountDue(),
            'message' => $this->message(),
        ];
    }

    public function toNextSms(object $notifiable): string
    {
        return $this->message();
    }

    private function message(): string
    {
        return sprintf(
            '%s: Loan %s payment of %s %s is due on %s.',
            config('microfinance.institution_name'),
            $this->installment->loan->loan_number,
            config('microfinance.currency'),
            number_format($this->amountDue(), 2),
            $this->installment->due_date->format('d M Y'),
        );
    }

    private function amountDue(): float
    {
        return collect(['principal', 'interest', 'fees', 'penalties'])
            ->sum(fn (string $component): float => $this->installment->outstandingFor($component) / 100);
    }
}
