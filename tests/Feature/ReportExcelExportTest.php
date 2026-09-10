<?php

use App\Exports\ReportWorkbookExport;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

test('accountant can download a branch scoped financial excel report', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $branch = Branch::factory()->create();
    $accountant = User::factory()->create(['branch_id' => $branch->id]);
    $accountant->assignRole('Accountant');

    $response = $this->actingAs($accountant)->get(route('reports.export', ['report' => 'financial']));

    $response->assertOk()->assertDownload();
    expect($response->headers->get('content-type'))->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('report catalogue renders formal report groups', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $auditor = User::factory()->create();
    $auditor->assignRole('Internal Auditor');

    $this->actingAs($auditor)
        ->get(route('reports.index', ['report' => 'bot-periodic-return']))
        ->assertOk()
        ->assertSee('Regulatory Reports')
        ->assertSee('BoT Periodic Return')
        ->assertSee('Credit Reference / CRB')
        ->assertSee('Audit &amp; Compliance', false);
});

test('financial workbook contains formatted detail formulas and reconciliation checks', function (): void {
    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $account = ChartOfAccount::factory()->create(['code' => '1000', 'name' => 'Cash', 'type' => 'Asset', 'normal_balance' => 'Debit']);
    $entry = JournalEntry::factory()->create(['branch_id' => $branch->id, 'journal_number' => 'JRN-TEST-001', 'posting_date' => today(), 'description' => 'Balanced test journal', 'status' => 'Posted', 'created_by' => $user->id, 'posted_by' => $user->id, 'posted_at' => now()]);
    JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'branch_id' => $branch->id, 'chart_of_account_id' => $account->id, 'debit' => 50000, 'credit' => 0]);
    JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'branch_id' => $branch->id, 'chart_of_account_id' => $account->id, 'debit' => 0, 'credit' => 50000]);
    $path = storage_path('framework/testing/financial-report.xlsx');

    (new ReportWorkbookExport('financial', $branch->id, null, null, $user->full_name))->writeTo($path);
    $workbook = IOFactory::load($path);

    expect($workbook->getSheetNames())->toBe(['Overview', 'General Ledger', 'Checks'])
        ->and($workbook->getSheetByName('General Ledger')?->getAutoFilter()->getRange())->toBe('A1:L3')
        ->and($workbook->getSheetByName('Overview')?->getCell('B10')->getValue())->toContain('SUM')
        ->and($workbook->getSheetByName('Checks')?->getCell('E3')->getCalculatedValue())->toBe('OK');
});

test('every supported report type builds an excel workbook', function (string $report, string $detailSheet): void {
    $user = User::factory()->create();
    $workbook = (new ReportWorkbookExport($report, null, null, null, $user->full_name))->spreadsheet();

    expect($workbook->getSheetNames())->toContain('Overview', $detailSheet, 'Checks');
})->with([
    ['operational', 'Applications'],
    ['portfolio', 'Loan Portfolio'],
    ['risk', 'Risk Portfolio'],
    ['collections', 'Repayments'],
    ['audit', 'Audit Log'],
]);
