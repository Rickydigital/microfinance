<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Notifications\CustomerActivityNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('customers:notify
    {customer : Customer number}
    {message : Notification message}
    {--subject=Account notification : Email and database subject}
    {--type=customer_activity : Machine-readable notification type}
    {--sync : Send immediately instead of queueing}')]
#[Description('Send a configured email, SMS and in-app notification to a customer')]
class SendCustomerNotificationCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $customer = Customer::query()->where('customer_number', $this->argument('customer'))->first();

        if ($customer === null) {
            $this->components->error('Customer not found.');

            return self::FAILURE;
        }

        $notification = new CustomerActivityNotification(
            subject: (string) $this->option('subject'),
            message: (string) $this->argument('message'),
            type: (string) $this->option('type'),
        );

        $this->option('sync') ? $customer->notifyNow($notification) : $customer->notify($notification);
        $this->components->info("Notification queued for {$customer->customer_number}.");

        return self::SUCCESS;
    }
}
