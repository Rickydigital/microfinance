<?php

use App\Data\RepaymentData;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\User;
use App\Services\RepaymentPostingService;
use App\Services\RepaymentReversalService;
use Carbon\CarbonImmutable;
use Database\Seeders\AccountingPeriodSeeder;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorized reversal preserves the original and restores loan schedule cash and accounting', function () {
    $this->seed([SystemSettingSeeder::class, ChartOfAccountSeeder::class, AccountingPeriodSeeder::class]);
    $branch = Branch::factory()->create();
    $cashier = User::factory()->for($branch)->create();
    $manager = User::factory()->for($branch)->create();
    $till = $cashier->tillSessions()->create(['branch_id' => $branch->id, 'opening_balance' => 0, 'expected_balance' => 0, 'status' => 'Open', 'opened_at' => now()]);
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create();
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create(['principal_amount' => 100000, 'outstanding_principal' => 100000]);
    $installment = LoanInstallment::factory()->for($loan)->create(['principal_due' => 100000, 'interest_due' => 0]);
    $repayment = app(RepaymentPostingService::class)->post(new RepaymentData($loan->id, $cashier->id, 'RP-TEST-1', 'TRX-TEST-1', '50000.00', 'Cash', CarbonImmutable::now()));

    $reversal = app(RepaymentReversalService::class)->reverse($repayment, $manager, 'Payment was posted to the wrong customer.');

    expect($repayment->refresh()->status)->toBe('Reversed')
        ->and($reversal->reversed_repayment_id)->toBe($repayment->id)
        ->and($loan->refresh()->outstanding_principal)->toBe('100000.00')
        ->and($installment->refresh()->principal_paid)->toBe('0.00')
        ->and($till->refresh()->expected_balance)->toBe('0.00')
        ->and($loan->transactions()->count())->toBe(2)
        ->and(JournalEntry::query()->count())->toBe(2);
});
