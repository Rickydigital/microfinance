<?php

use App\LoanApplicationStatus;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->givePermissionTo(['dashboard.view', 'loan.view', 'loan.application.view', 'loan.application.create', 'loan.application.update', 'loan.disbursement.create']);
});

test('application register presents lifecycle metrics filters and creation modal', function (): void {
    $application = LoanApplication::factory()->create(['branch_id' => $this->staff->branch_id, 'status' => LoanApplicationStatus::Approved, 'approved_amount' => 500000]);

    $this->actingAs($this->staff)->get(route('loan-applications.index'))
        ->assertOk()
        ->assertSee('Loan lifecycle')
        ->assertSee('Pending review')
        ->assertSee('id="new-application"', false)
        ->assertSee('id="application-'.$application->id.'"', false)
        ->assertSee('id="disburse-'.$application->id.'"', false)
        ->assertSee('Prepare disbursement')
        ->assertSee("href=\"#application-{$application->id}\"", false)
        ->assertSee($application->application_number)
        ->assertSee('name="product_id"', false)
        ->assertSee('name="from"', false);
});

test('calendar opens a selected day with installment details', function (): void {
    $loan = Loan::factory()->create(['branch_id' => $this->staff->branch_id]);
    $installment = LoanInstallment::factory()->for($loan)->create(['due_date' => today()->addDays(2), 'status' => 'Pending']);

    $this->actingAs($this->staff)->get(route('loan-operations.calendar', ['month' => $installment->due_date->format('Y-m')]))
        ->assertOk()
        ->assertSee('Loan Calendar')
        ->assertSee('id="calendar-day-'.$installment->due_date->format('Ymd').'"', false)
        ->assertSee($loan->loan_number);
});

test('staff sidebar exposes calendar and loan ageing as first level navigation', function (): void {
    $this->actingAs($this->staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-tooltip="Loan calendar"', false)
        ->assertSee('data-tooltip="Loan ageing"', false)
        ->assertSee(route('loan-operations.calendar'), false)
        ->assertSee(route('loan-operations.ageing'), false);
});

test('ageing groups overdue balances into operational risk buckets', function (): void {
    $loan = Loan::factory()->create(['branch_id' => $this->staff->branch_id]);
    LoanInstallment::factory()->for($loan)->create([
        'due_date' => today()->subDays(35), 'principal_due' => 500000, 'principal_paid' => 100000,
        'interest_due' => 50000, 'interest_paid' => 0, 'status' => 'Partially Paid',
    ]);

    $this->actingAs($this->staff)->get(route('loan-operations.ageing'))
        ->assertOk()
        ->assertSee('31–60 days')
        ->assertSee('TZS 450,000')
        ->assertSee($loan->loan_number);
});

test('loan profile presents portfolio metrics and visual repayment analysis', function (): void {
    $loan = Loan::factory()->create(['branch_id' => $this->staff->branch_id]);
    LoanInstallment::factory()->for($loan)->create(['principal_due' => 500000, 'interest_due' => 50000, 'principal_paid' => 250000, 'status' => 'Partially Paid']);

    $this->actingAs($this->staff)->get(route('loans.show', $loan))
        ->assertOk()
        ->assertSee('Repayment progress')
        ->assertSee('Contract composition')
        ->assertSee('Repayment schedule')
        ->assertSee('progress-ring', false);
});

test('loan register presents portfolio metrics and exposure charts', function (): void {
    $loan = Loan::factory()->create(['branch_id' => $this->staff->branch_id]);

    $this->actingAs($this->staff)->get(route('loans.index'))
        ->assertOk()
        ->assertSee('Portfolio by status')
        ->assertSee('Product exposure')
        ->assertSee('Outstanding principal')
        ->assertSee($loan->loan_number);
});
