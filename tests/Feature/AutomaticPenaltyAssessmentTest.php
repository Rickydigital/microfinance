<?php

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Services\PenaltyAssessmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('daily penalty assessment is automatic and idempotent for a business date', function (): void {
    $version = LoanProductVersion::factory()->create();
    $version->penaltyRules()->create(['name' => 'Late principal', 'calculation_method' => 'Percentage', 'calculation_basis' => 'Overdue Principal', 'value' => 0.10, 'grace_days' => 0, 'frequency' => 'Daily', 'maximum_amount' => 250, 'is_active' => true]);
    $application = LoanApplication::factory()->create(['loan_product_version_id' => $version->id]);
    $loan = Loan::factory()->create(['loan_application_id' => $application->id, 'branch_id' => $application->branch_id, 'customer_id' => $application->customer_id]);
    $installment = $loan->installments()->create(['installment_number' => 1, 'due_date' => today()->subDays(2), 'principal_due' => 1000, 'interest_due' => 100, 'fees_due' => 0, 'penalties_due' => 0, 'status' => 'Pending']);
    $businessDate = CarbonImmutable::today();

    $first = app(PenaltyAssessmentService::class)->assessLoan($loan, $businessDate);
    $second = app(PenaltyAssessmentService::class)->assessLoan($loan->fresh(), $businessDate);

    expect($first)->toMatchArray(['charges' => 1, 'amount' => 100.0])
        ->and($second)->toMatchArray(['charges' => 0, 'amount' => 0.0])
        ->and((float) $installment->fresh()->penalties_due)->toBe(100.0);
});
