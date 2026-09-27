<?php

use App\LoanStatus;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\User;
use App\Services\LoanQuotationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('quotation exposes numeric interest workings and distributes configured fees', function (): void {
    $version = LoanProductVersion::factory()->create(['interest_method' => 'Reducing Balance', 'interest_rate' => 0.24, 'repayment_frequency' => 'Monthly']);
    $version->fees()->create(['name' => 'Service fee', 'calculation_method' => 'Percentage', 'value' => 0.02, 'collection_point' => 'Installment', 'is_financed' => false]);

    $quote = app(LoanQuotationService::class)->quote($version->load('fees'), 200000, 2, 500000, 100000, 0);

    expect($quote['annual_interest_rate'])->toBe(0.24)
        ->and($quote['periodic_rate'])->toBe(0.02)
        ->and($quote['total_fees'])->toBe(10000.0)
        ->and($quote['projected_schedule'][0]['interest'])->toBe(4000.0)
        ->and($quote['projected_schedule'][0]['interest_calculation'])->toContain('200,000.00 × 2.000000% = 4,000.00')
        ->and(collect($quote['projected_schedule'])->sum('fees'))->toBe(10000.0);
});

test('repayment selection hides fully paid loans and can search unpaid loans', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('System Administrator');
    $application = LoanApplication::factory()->create(['branch_id' => $user->branch_id, 'created_by' => $user->id]);
    $paidApplication = LoanApplication::factory()->create(['branch_id' => $user->branch_id, 'customer_id' => $application->customer_id, 'created_by' => $user->id]);
    $unpaid = Loan::factory()->create(['loan_application_id' => $application->id, 'branch_id' => $user->branch_id, 'customer_id' => $application->customer_id, 'loan_number' => 'LN-UNPAID', 'status' => LoanStatus::Active]);
    $paid = Loan::factory()->create(['loan_application_id' => $paidApplication->id, 'branch_id' => $user->branch_id, 'customer_id' => $application->customer_id, 'loan_number' => 'LN-PAID', 'status' => LoanStatus::Active]);
    $unpaid->installments()->create(['installment_number' => 1, 'due_date' => today(), 'principal_due' => 1000, 'interest_due' => 100, 'fees_due' => 0, 'penalties_due' => 0, 'status' => 'Pending']);
    $paid->installments()->create(['installment_number' => 1, 'due_date' => today(), 'principal_due' => 1000, 'principal_paid' => 1000, 'interest_due' => 100, 'interest_paid' => 100, 'fees_due' => 0, 'penalties_due' => 0, 'status' => 'Paid']);

    $this->actingAs($user)->get(route('repayments.create', ['search' => 'LN']))
        ->assertOk()
        ->assertSee('LN-UNPAID')
        ->assertDontSee('LN-PAID');
});
