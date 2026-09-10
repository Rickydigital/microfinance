<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Services\LoanScheduleService;
use Carbon\CarbonImmutable;
use Database\Seeders\LoanProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('schedule generation reconciles principal and creates every monthly installment', function () {
    $this->seed(LoanProductSeeder::class);
    $branch = Branch::factory()->create();
    $customer = Customer::factory()->for($branch)->create();
    $application = LoanApplication::factory()->for($branch)->for($customer)->create([
        'loan_product_version_id' => LoanProductVersion::query()->firstOrFail()->id,
        'requested_term' => 12,
    ]);
    $loan = Loan::factory()->for($branch)->for($customer)->for($application, 'application')->create([
        'principal_amount' => 1000000,
        'outstanding_principal' => 1000000,
    ]);

    app(LoanScheduleService::class)->generate($loan, CarbonImmutable::parse('2026-09-01'));

    expect($loan->installments()->count())->toBe(12)
        ->and((float) $loan->installments()->sum('principal_due'))->toBe(1000000.0)
        ->and($loan->installments()->orderBy('installment_number')->first()->due_date->toDateString())->toBe('2026-09-01');
});
