<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Notifications\LoanPaymentReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('configured upcoming installment reminders are sent once per business day', function () {
    Notification::fake();
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create();
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create();
    $installment = LoanInstallment::factory()->for($loan)->create(['due_date' => today()->addDays(30)]);

    $this->artisan('loans:send-reminders', ['--date' => today()->toDateString()])
        ->expectsOutputToContain('1 loan reminder(s) queued.')
        ->assertSuccessful();

    Notification::assertSentTo($customer, LoanPaymentReminderNotification::class);
    expect($installment->refresh()->reminder_last_sent_at)->not->toBeNull();
});
