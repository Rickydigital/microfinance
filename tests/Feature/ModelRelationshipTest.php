<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\TillSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff, customer, application and loan relationships preserve branch scope', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->for($branch)->create();
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create();
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create();
    $tillSession = TillSession::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'opening_balance' => 100000,
        'status' => 'Open',
        'opened_at' => now(),
    ]);

    expect($user->branch->is($branch))->toBeTrue()
        ->and($user->full_name)->not->toBeEmpty()
        ->and($customer->loanApplications->first()->is($application))->toBeTrue()
        ->and($application->loan->is($loan))->toBeTrue()
        ->and($loan->customer->is($customer))->toBeTrue()
        ->and($tillSession->user->is($user))->toBeTrue();
});
