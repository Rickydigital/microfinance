<?php

use App\CustomerStatus;
use App\LoanApplicationStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\User;
use App\Services\CustomerKycReadinessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('quotation explains formulas and includes configured fees', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Loan Officer');
    $version = LoanProductVersion::factory()->create();
    $version->fees()->create(['name' => 'Processing fee', 'calculation_method' => 'Percentage', 'value' => 0.02, 'collection_point' => 'Disbursement', 'is_financed' => false]);

    $response = $this->actingAs($user)->postJson(route('loan-applications.quote'), [
        'loan_product_version_id' => $version->id,
        'requested_amount' => 1000000,
        'requested_term' => 12,
        'monthly_income' => 500000,
        'monthly_expenses' => 100000,
        'existing_debt_payment' => 0,
    ])->assertOk()->assertJsonPath('principal', 1000000)
        ->assertJsonPath('total_fees', 50000)
        ->assertJsonCount(2, 'fees')
        ->assertJsonStructure(['formula', 'affordability_formula', 'policy', 'total_interest', 'total_repayment']);

    expect(round(collect($response->json('projected_schedule'))->sum('principal'), 2))->toEqual(1000000.0)
        ->and(round(collect($response->json('projected_schedule'))->sum('installment'), 2))->toEqual($response->json('total_repayment'));
});

test('a permitted officer can update a draft application', function (): void {
    $branch = Branch::factory()->create();
    $officer = User::factory()->create(['branch_id' => $branch->id]);
    $officer->assignRole('Loan Officer');
    $customer = Customer::factory()->create(['branch_id' => $branch->id, 'status' => CustomerStatus::Active]);
    $version = LoanProductVersion::factory()->create();
    $application = LoanApplication::factory()->create(['branch_id' => $branch->id, 'customer_id' => $customer->id, 'created_by' => $officer->id, 'loan_product_version_id' => $version->id, 'status' => LoanApplicationStatus::Draft]);

    $this->actingAs($officer)->put(route('loan-applications.update', $application), [
        'customer_id' => $customer->id,
        'loan_product_version_id' => $version->id,
        'requested_amount' => 750000,
        'requested_term' => 10,
        'repayment_frequency' => $version->repayment_frequency,
        'purpose' => 'Purchase additional business inventory for expansion.',
        'declared_monthly_income' => 500000,
        'declared_monthly_expenses' => 100000,
        'existing_debt_payment' => 0,
    ])->assertRedirect(route('loan-applications.show', $application))->assertSessionHasNoErrors();

    expect($application->fresh()->requested_amount)->toBe('750000.00')->and($application->fresh()->requested_term)->toBe(10);
});

test('kyc readiness includes configured mandatory documents', function (): void {
    $customer = Customer::factory()->create(['status' => CustomerStatus::PendingVerification, 'verified_at' => null]);
    DocumentType::factory()->create(['code' => 'INCOME_PROOF', 'name' => 'Income proof', 'is_active' => true, 'is_required_for_kyc' => true, 'customer_type_code' => null]);

    $result = app(CustomerKycReadinessService::class)->evaluate($customer);

    expect($result['ready'])->toBeFalse()->and(collect($result['checks'])->pluck('label'))->toContain('Income proof');
});

test('recorded kyc approval is authoritative for loan readiness', function (): void {
    $customer = Customer::factory()->create([
        'status' => CustomerStatus::Active,
        'verified_at' => now(),
    ]);

    $result = app(CustomerKycReadinessService::class)->evaluate($customer);

    expect($result['approved'])->toBeTrue()
        ->and($result['ready'])->toBeTrue()
        ->and($result['completed'])->toBeLessThan($result['total']);
});

test('loan application view renders the complete credit dossier', function (): void {
    $branch = Branch::factory()->create();
    $officer = User::factory()->create(['branch_id' => $branch->id]);
    $officer->assignRole('Loan Officer');
    $application = LoanApplication::factory()->create(['branch_id' => $branch->id, 'created_by' => $officer->id]);

    $this->actingAs($officer)->get(route('loan-applications.show', $application))
        ->assertOk()
        ->assertSee('Complete credit dossier')
        ->assertSee('Available actions')
        ->assertSee('Assess application')
        ->assertSee('More details')
        ->assertSee('Requested principal')
        ->assertSee('Decision snapshot')
        ->assertSee('Affordability usage')
        ->assertSee('Product &amp; quotation', false)
        ->assertSee('loan-panel-modal')
        ->assertSee('Affordability calculation')
        ->assertSee('Projected repayment schedule')
        ->assertSee('Application timeline');
});

test('draft affordability uses the customers current economic profile', function (): void {
    $branch = Branch::factory()->create();
    $officer = User::factory()->create(['branch_id' => $branch->id]);
    $officer->assignRole('Loan Officer');
    $customer = Customer::factory()->create(['branch_id' => $branch->id, 'status' => CustomerStatus::Active]);
    $customer->economicProfiles()->create([
        'occupation' => 'Trading',
        'income_source' => 'Business',
        'monthly_income' => 4500000,
        'monthly_expenses' => 100000,
        'existing_debt_payment' => 0,
        'effective_from' => today(),
    ]);
    $application = LoanApplication::factory()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $officer->id,
        'status' => LoanApplicationStatus::Draft,
        'declared_monthly_income' => 0,
        'declared_monthly_expenses' => 0,
        'existing_debt_payment' => 0,
    ]);

    $this->actingAs($officer)->get(route('loan-applications.show', $application))
        ->assertOk()
        ->assertSee('Limit: TZS 2,200,000.00')
        ->assertDontSee('affordability limit of 0.00');
});
