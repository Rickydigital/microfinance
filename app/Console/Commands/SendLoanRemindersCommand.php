<?php

namespace App\Console\Commands;

use App\Models\LoanInstallment;
use App\Notifications\LoanPaymentReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('loans:send-reminders {--date= : Business date in YYYY-MM-DD format} {--sync : Send immediately instead of queueing}')]
#[Description('Queue configured upcoming loan-installment reminders for customers')]
class SendLoanRemindersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $businessDate = $this->option('date') ? CarbonImmutable::parse($this->option('date'))->startOfDay() : today()->toImmutable();
        $reminderDays = config('microfinance.notifications.loan_reminder_days');
        $sent = 0;

        LoanInstallment::query()
            ->with('loan.customer')
            ->whereIn('status', ['Pending', 'Partially Paid'])
            ->whereBetween('due_date', [$businessDate, $businessDate->addDays(max($reminderDays))])
            ->where(function ($query) use ($businessDate): void {
                $query->whereNull('reminder_last_sent_at')->orWhereDate('reminder_last_sent_at', '<', $businessDate);
            })
            ->orderBy('id')
            ->chunkById(200, function ($installments) use ($businessDate, $reminderDays, &$sent): void {
                foreach ($installments as $installment) {
                    $daysUntilDue = (int) round($businessDate->diffInDays($installment->due_date, false));

                    if (! in_array($daysUntilDue, $reminderDays, true)) {
                        continue;
                    }

                    $customer = $installment->loan->customer;
                    $notification = new LoanPaymentReminderNotification($installment);

                    if ($this->option('sync')) {
                        $customer->notifyNow($notification);
                    } else {
                        $customer->notify($notification);
                    }
                    $installment->forceFill(['reminder_last_sent_at' => now()])->save();
                    $sent++;
                }
            });

        $this->components->info("{$sent} loan reminder(s) queued.");

        return self::SUCCESS;
    }
}
