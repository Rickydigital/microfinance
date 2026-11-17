<?php

use App\Exports\BotReturnWorkbookExport;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\User;
use App\Services\BotReturnService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('portfolio reporter can open filter and export the BOT return workspace', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('Internal Auditor');

    $this->actingAs($user)->get(route('bot-returns.index', ['year' => 2026, 'quarter' => 2]))
        ->assertOk()->assertSee('BOT Quarterly Return')->assertSee('Balance Sheet')->assertSee('Portfolio &amp; Aging', false)->assertSee('Built-in Validation Checks');

    $this->actingAs($user)->get(route('bot-returns.export', ['year' => 2026, 'quarter' => 2]))
        ->assertOk()->assertDownload('BOT-return-2026-Q2.xlsx');
});

test('quarterly service distinguishes stock balances from quarter flows', function (): void {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $asset = ChartOfAccount::factory()->create(['code' => '1000', 'name' => 'Cash at bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_active' => true]);
    $income = ChartOfAccount::factory()->create(['code' => '4000', 'name' => 'Interest income', 'type' => 'Income', 'normal_balance' => 'Credit', 'is_active' => true]);
    $entry = JournalEntry::factory()->create(['branch_id' => $branch->id, 'journal_number' => 'JRN-BOT-001', 'posting_date' => '2026-04-15', 'status' => 'Posted', 'description' => 'Quarter test', 'created_by' => $user->id]);
    JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'branch_id' => $branch->id, 'chart_of_account_id' => $asset->id, 'debit' => 100000, 'credit' => 0]);
    JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'branch_id' => $branch->id, 'chart_of_account_id' => $income->id, 'debit' => 0, 'credit' => 100000]);

    $return = app(BotReturnService::class)->build(2026, 2, $branch->id);

    expect($return['balance_sheet']['assets'])->toBe(100000.0)
        ->and($return['income_statement']['income'])->toBe(100000.0)
        ->and($return['period']['end']->format('Y-m-d'))->toBe('2026-06-30');
});

test('BOT review workbook contains all compliance schedules', function (): void {
    $return = app(BotReturnService::class)->build(2026, 1, null);
    $book = (new BotReturnWorkbookExport($return))->spreadsheet();

    expect($book->getSheetNames())->toBe(['Balance Sheet', 'Income Statement', 'Portfolio & Aging', 'Loan Detail', 'Liquid Assets', 'Interest Rates', 'Validations']);
});

test('BOT return defaults to the latest quarter containing loan activity', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('Internal Auditor');
    Loan::factory()->create(['branch_id' => $branch->id, 'disbursed_at' => '2025-08-12']);

    $this->actingAs($user)->get(route('bot-returns.index'))
        ->assertOk()
        ->assertSee('Q3 · Jul–Sep')
        ->assertSee('01 Jul 2025 – 30 Sep 2025');
});

test('BOT return supports a custom reporting range with trend analytics', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Internal Auditor');

    $this->actingAs($user)->get(route('bot-returns.index', ['period_mode' => 'custom', 'from' => '2025-02-01', 'to' => '2025-05-15']))
        ->assertOk()
        ->assertSee('01 Feb 2025 – 15 May 2025')
        ->assertSee('8-QUARTER PERFORMANCE')
        ->assertSee('Custom date range');
});
