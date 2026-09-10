<?php

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('accountant can use chart of accounts journals and financial reports', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $accountant = User::factory()->create();
    $accountant->assignRole('Accountant');

    $this->actingAs($accountant)->post(route('finance.accounts.store'), ['code' => '5100', 'name' => 'Office supplies', 'type' => 'Expense', 'normal_balance' => 'Debit', 'allows_manual_posting' => 1])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('chart_of_accounts', ['code' => '5100', 'name' => 'Office supplies']);
    $this->actingAs($accountant)->get(route('finance.accounts.index'))->assertOk()->assertSee('Office supplies');
    $this->actingAs($accountant)->get(route('finance.journals.index'))->assertOk()->assertSee('Journal entries');
    $this->actingAs($accountant)->get(route('reports.index', ['report' => 'financial']))->assertOk()->assertSee('Balance Sheet');
    expect(ChartOfAccount::query()->count())->toBe(1);
});
