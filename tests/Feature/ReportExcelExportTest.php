<?php

use App\Exports\ReportWorkbookExport;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\MyCashHistoricalLoansSeeder;
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
        ->assertSee('.report-hero h1,.report-hero p', false)
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

test('collection PDF opens inline with full repayment records', function (): void {
    $this->seed([InstitutionSeeder::class, BranchSeeder::class, RolePermissionSeeder::class, MyCashHistoricalLoansSeeder::class]);
    $branch = Branch::query()->where('code', config('microfinance.defaults.branch.code'))->firstOrFail();
    $auditor = User::factory()->create(['branch_id' => $branch->id]);
    $auditor->assignRole('Internal Auditor');

    $response = $this->actingAs($auditor)->get(route('reports.pdf', ['report' => 'repayments']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('inline');
});

test('report analytics use business dates and show disbursement and repayment trends', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $branch = Branch::factory()->create();
    $auditor = User::factory()->create(['branch_id' => $branch->id]);
    $auditor->assignRole('Internal Auditor');
    $loan = Loan::factory()->create([
        'branch_id' => $branch->id,
        'principal_amount' => 1000000,
        'outstanding_principal' => 750000,
        'disbursed_at' => '2020-01-15',
    ]);
    Repayment::factory()->create([
        'loan_id' => $loan->id,
        'receipt_number' => 'RCT-TREND-001',
        'amount' => 125000,
        'status' => 'Posted',
        'payment_method' => 'Bank Transfer',
        'received_by' => $auditor->id,
        'paid_at' => '2020-03-10',
        'created_at' => now(),
    ]);

    $this->actingAs($auditor)->get(route('reports.index', ['report' => 'portfolio-summary']))
        ->assertOk()
        ->assertSee('TZS 1,000,000.00')
        ->assertSee('TZS 125,000.00')
        ->assertSee('Jan 20')
        ->assertSee('Mar 20')
        ->assertSee('Portfolio aging exposure')
        ->assertSee('Repayment method mix');
});
