<?php

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanProductVersion;
use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->assignRole('System Administrator');
});

test('staff can prepare a policy driven loan quotation', function (): void {
    $version = LoanProductVersion::factory()->create([
        'interest_method' => 'Flat',
        'interest_rate' => 0.10,
        'rate_basis' => 'Whole Term',
        'repayment_frequency' => 'Monthly',
        'assurance_percent' => 0,
        'minimum_amount' => 100000,
        'maximum_amount' => 5000000,
        'minimum_term' => 1,
        'maximum_term' => 12,
    ]);
    $version->fees()->create([
        'name' => 'Processing fee',
        'calculation_method' => 'Fixed',
        'value' => 15000,
        'collection_point' => 'Installment',
        'is_financed' => true,
    ]);

    $this->actingAs($this->staff)->get(route('loan-calculator.index', [
        'loan_product_version_id' => $version->id,
        'amount' => 1500000,
        'term' => 6,
        'monthly_income' => 600000,
        'monthly_expenses' => 100000,
        'existing_debt_payment' => 0,
    ]))->assertOk()
        ->assertSee('Loan calculator')
        ->assertSee('TZS 277,500.00')
        ->assertSee('TZS 150,000.00')
        ->assertSee('TZS 1,665,000.00')
        ->assertSee('TZS 1,500,000.00')
        ->assertSee('Projected repayment schedule')
        ->assertSee('Processing fee')
        ->assertSee('Automatic calculation');
});

test('loan calculator is available from the configured sidebar', function (): void {
    expect(collect(config('microfinance.navigation'))->flatMap(fn (array $item) => $item['items'] ?? [])->pluck('route'))
        ->toContain('loan-calculator.index');
});

test('existing product processing fees are normalized to one distributed fixed charge', function (): void {
    $version = LoanProductVersion::factory()->create();
    $version->fees()->create([
        'name' => 'Processing Fee',
        'calculation_method' => 'Percentage',
        'value' => 0.02,
        'collection_point' => 'Disbursement',
        'is_financed' => false,
    ]);

    $migration = require database_path('migrations/2026_11_15_124431_standardize_processing_fees_across_loan_products.php');
    $migration->up();

    $fee = $version->fees()->whereRaw('LOWER(name) = ?', ['processing fee'])->sole();
    expect((float) $fee->value)->toBe(15000.0)
        ->and($fee->calculation_method)->toBe('Fixed')
        ->and($fee->collection_point)->toBe('Installment')
        ->and($fee->is_financed)->toBeTrue();
});

test('disbursement summary defaults to the same all time scope as its register', function (): void {
    $application = LoanApplication::factory()->create([
        'branch_id' => $this->staff->branch_id,
        'created_by' => $this->staff->id,
        'status' => 'Converted to Loan',
    ]);
    LoanDisbursement::query()->create([
        'loan_application_id' => $application->id,
        'branch_id' => $this->staff->branch_id,
        'disbursement_number' => 'DSB-HISTORICAL-01',
        'gross_amount' => 2200000,
        'deducted_fees' => 15000,
        'net_amount' => 2185000,
        'method' => 'Bank',
        'status' => 'Disbursed',
        'created_by' => $this->staff->id,
        'authorized_by' => $this->staff->id,
        'authorized_at' => now()->subYear(),
        'disbursed_at' => now()->subYear(),
    ]);

    $this->actingAs($this->staff)->get(route('loan-disbursements.index'))
        ->assertOk()
        ->assertSee('All recorded disbursements')
        ->assertSee('TZS 2,200,000')
        ->assertSee('TZS 2,185,000')
        ->assertSee('DSB-HISTORICAL-01');
});

test('repayment modal previews the selected loan balance and all time collections', function (): void {
    $loan = Loan::factory()->create(['branch_id' => $this->staff->branch_id]);
    $loan->installments()->create([
        'installment_number' => 1,
        'due_date' => today(),
        'principal_due' => 100000,
        'interest_due' => 10000,
        'fees_due' => 5000,
        'penalties_due' => 2000,
        'status' => 'Pending',
    ]);
    Repayment::query()->create([
        'loan_id' => $loan->id,
        'receipt_number' => 'RCT-HISTORICAL-01',
        'amount' => 25000,
        'status' => 'Posted',
        'payment_method' => 'Cash',
        'received_by' => $this->staff->id,
        'paid_at' => now()->subYear(),
    ]);

    $this->actingAs($this->staff)->get(route('repayments.index'))
        ->assertOk()
        ->assertSee('Total posted')
        ->assertSee('TZS 25,000')
        ->assertSee('data-payment-balance', false)
        ->assertSee('data-total="117000"', false)
        ->assertSee('Post payment and issue receipt');
});
