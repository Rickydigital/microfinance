<?php

use App\Data\RepaymentData;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\User;
use App\Services\RepaymentPostingService;
use Carbon\CarbonImmutable;
use Database\Seeders\AccountingPeriodSeeder;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([SystemSettingSeeder::class, ChartOfAccountSeeder::class, AccountingPeriodSeeder::class]);
});

test('repayment is allocated in policy order and updates principal atomically', function () {
    $branch = Branch::factory()->create();
    $cashier = User::factory()->for($branch)->create();
    $cashier->tillSessions()->create(['branch_id' => $branch->id, 'opening_balance' => 0, 'expected_balance' => 0, 'status' => 'Open', 'opened_at' => now()]);
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create();
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create([
        'principal_amount' => 300000,
        'outstanding_principal' => 300000,
    ]);

    LoanInstallment::factory()->for($loan)->create([
        'penalties_due' => 10000,
        'fees_due' => 5000,
        'interest_due' => 35000,
        'principal_due' => 300000,
    ]);

    $repayment = app(RepaymentPostingService::class)->post(new RepaymentData(
        loanId: $loan->id,
        receivedBy: $cashier->id,
        receiptNumber: 'RP-000001',
        transactionNumber: 'TRX-000001',
        amount: '150000.00',
        paymentMethod: 'Cash',
        paidAt: CarbonImmutable::now(),
        idempotencyKey: 'cashier-test-1',
    ));

    expect($repayment->allocations->pluck('component')->all())
        ->toBe(['penalties', 'fees', 'interest', 'principal'])
        ->and($repayment->allocations->sum('amount'))->toEqual(150000.0)
        ->and($loan->refresh()->outstanding_principal)->toBe('200000.00')
        ->and($loan->transactions)->toHaveCount(1);
});

test('idempotency key prevents duplicate financial posting', function () {
    $branch = Branch::factory()->create();
    $cashier = User::factory()->for($branch)->create();
    $cashier->tillSessions()->create(['branch_id' => $branch->id, 'opening_balance' => 0, 'expected_balance' => 0, 'status' => 'Open', 'opened_at' => now()]);
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create();
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create();
    LoanInstallment::factory()->for($loan)->create(['principal_due' => 1000000, 'interest_due' => 0]);

    $data = new RepaymentData(
        loanId: $loan->id,
        receivedBy: $cashier->id,
        receiptNumber: 'RP-000002',
        transactionNumber: 'TRX-000002',
        amount: '100000.00',
        paymentMethod: 'Cash',
        paidAt: CarbonImmutable::now(),
        idempotencyKey: 'cashier-test-2',
    );

    $service = app(RepaymentPostingService::class);
    $first = $service->post($data);
    $second = $service->post($data);

    expect($second->is($first))->toBeTrue()
        ->and($loan->repayments)->toHaveCount(1)
        ->and($loan->transactions)->toHaveCount(1);
});
