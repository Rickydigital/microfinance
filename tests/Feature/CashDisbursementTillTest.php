<?php

use App\Models\LoanApplication;
use App\Models\TillSession;
use App\Models\User;
use App\Services\CashTransactionService;
use Database\Seeders\SystemSettingSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cash out requires an open till and reduces expected cash', function (): void {
    $this->seed(SystemSettingSeeder::class);
    $user = User::factory()->create();
    $source = LoanApplication::factory()->create(['branch_id' => $user->branch_id, 'created_by' => $user->id]);

    expect(fn () => app(CashTransactionService::class)->post($user, 'Loan Disbursement', 'Out', '250.00', $source, true))->toThrow(DomainException::class);

    $till = TillSession::factory()->create(['branch_id' => $user->branch_id, 'user_id' => $user->id, 'opening_balance' => 1000, 'expected_balance' => 1000]);
    $transaction = app(CashTransactionService::class)->post($user, 'Loan Disbursement', 'Out', '250.00', $source, true);

    expect((float) $till->fresh()->expected_balance)->toBe(750.0)
        ->and($transaction->till_session_id)->toBe($till->id)
        ->and($transaction->direction)->toBe('Out');
});
