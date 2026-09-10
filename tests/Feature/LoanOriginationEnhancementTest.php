<?php

use App\LoanApplicationStatus;
use App\Models\ApprovalRule;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('loan quotation calculates installment and affordability on the server', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Loan Officer');
    $version = LoanProductVersion::factory()->create();

    $response = $this->actingAs($user)->postJson(route('loan-applications.quote'), [
        'loan_product_version_id' => $version->id,
        'requested_amount' => 1000000,
        'requested_term' => 12,
        'monthly_income' => 500000,
        'monthly_expenses' => 100000,
        'existing_debt_payment' => 0,
    ]);

    $response->assertOk()->assertJsonStructure(['installment', 'total_interest', 'total_repayment', 'recommended_amount', 'affordable']);
    expect($response->json('installment'))->toBeGreaterThan(0)->and($response->json('affordable'))->toBeTrue();
});

test('assessment calculates installment and creates visible approval route', function (): void {
    $branch = Branch::factory()->create();
    $maker = User::factory()->create(['branch_id' => $branch->id]);
    $maker->assignRole('Credit Officer');
    $version = LoanProductVersion::factory()->create();
    $customer = Customer::factory()->create(['branch_id' => $branch->id]);
    $application = LoanApplication::factory()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $maker->id,
        'loan_product_version_id' => $version->id,
        'requested_amount' => 1000000,
        'status' => LoanApplicationStatus::Submitted,
    ]);
    ApprovalRule::factory()->create([
        'loan_product_id' => $version->loan_product_id,
        'branch_id' => null,
        'minimum_amount' => 0,
        'maximum_amount' => 2000000,
        'role_name' => 'Credit Manager',
        'sequence' => 1,
    ]);

    $response = $this->actingAs($maker)->post(route('loan-applications.assessments.store', $application), [
        'monthly_income' => 500000,
        'monthly_expenses' => 100000,
        'existing_debt_payment' => 0,
        'risk_grade' => 'A',
        'recommendation' => 'Recommended',
        'rationale' => 'Stable verified cash flow supports the proposed facility.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loan_assessments', ['loan_application_id' => $application->id, 'recommendation' => 'Recommended']);
    $this->assertDatabaseHas('loan_approvals', ['loan_application_id' => $application->id, 'required_role' => 'Credit Manager', 'status' => 'Pending']);
    expect($application->fresh()->status)->toBe(LoanApplicationStatus::PendingApproval);
});

test('application details show assessment and an approver action', function (): void {
    $branch = Branch::factory()->create();
    $approver = User::factory()->create(['branch_id' => $branch->id]);
    $approver->assignRole('Credit Manager');
    $application = LoanApplication::factory()->create(['branch_id' => $branch->id, 'status' => LoanApplicationStatus::PendingApproval]);
    $application->approvals()->create(['sequence' => 1, 'required_role' => 'Credit Manager', 'status' => 'Pending']);

    $this->actingAs($approver)->get(route('loan-applications.show', $application))
        ->assertOk()
        ->assertSee('Decision required from Credit Manager')
        ->assertSee('Approvals');
});

test('system administrator can decide any configured loan approval level', function (): void {
    $branch = Branch::factory()->create();
    $maker = User::factory()->create(['branch_id' => $branch->id]);
    $administrator = User::factory()->create(['branch_id' => null]);
    $administrator->assignRole('System Administrator');
    $application = LoanApplication::factory()->create(['branch_id' => $branch->id, 'created_by' => $maker->id, 'status' => LoanApplicationStatus::PendingApproval]);
    $approval = $application->approvals()->create(['sequence' => 1, 'required_role' => 'CEO', 'status' => 'Pending']);

    $this->actingAs($administrator)->put(route('loan-approvals.update', $approval), [
        'decision' => 'Approved',
        'comments' => 'Administrator approval under full system authority.',
    ])->assertSessionHasNoErrors();

    expect($approval->fresh()->status)->toBe('Approved')
        ->and($application->fresh()->status)->toBe(LoanApplicationStatus::Approved);
});
