<?php

use App\Models\ChartOfAccount;
use App\Models\LoanProductVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a product version owns fees penalties and ledger mappings', function (): void {
    $version = LoanProductVersion::factory()->create();
    $account = ChartOfAccount::query()->create(['code' => '1100', 'name' => 'Loan receivable', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_control_account' => true, 'allows_manual_posting' => false, 'is_active' => true]);

    $version->fees()->create(['name' => 'Processing', 'calculation_method' => 'Percentage', 'value' => 0.02, 'collection_point' => 'Installment', 'is_financed' => true]);
    $version->penaltyRules()->create(['name' => 'Daily arrears', 'calculation_method' => 'Percentage', 'calculation_basis' => 'Overdue Principal', 'value' => 0.01, 'grace_days' => 2, 'frequency' => 'Daily', 'maximum_amount' => 50000, 'is_active' => true]);
    $version->accountMappings()->create(['purpose' => 'loan_receivable', 'chart_of_account_id' => $account->id]);

    expect($version->fees()->count())->toBe(1)
        ->and($version->penaltyRules()->count())->toBe(1)
        ->and($version->accountMappings()->where('purpose', 'loan_receivable')->exists())->toBeTrue();
});
