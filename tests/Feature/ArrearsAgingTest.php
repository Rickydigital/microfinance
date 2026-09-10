<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Services\ArrearsAgingService;
use Carbon\CarbonImmutable;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('daily aging updates dpd and arrears and opens one recovery case', function () {
    $this->seed(SystemSettingSeeder::class);
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create();
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create();
    LoanInstallment::factory()->for($loan)->create(['due_date' => '2026-08-01', 'principal_due' => 100000, 'interest_due' => 10000]);

    $result = app(ArrearsAgingService::class)->run(CarbonImmutable::parse('2026-08-11'));

    expect($loan->refresh()->days_past_due)->toBe(10)
        ->and($loan->arrears_amount)->toBe('110000.00')
        ->and($loan->status->value)->toBe('In Arrears')
        ->and($loan->collectionCases()->count())->toBe(1)
        ->and($result['casesCreated'])->toBe(1);
});
