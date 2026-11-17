<?php

use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use App\Models\NumberSequence;
use App\Models\PaymentSubmission;
use App\Models\Repayment;
use App\Models\User;
use App\Services\AccountingPostingService;
use App\Services\DashboardMetricsService;
use App\Services\HistoricalLoanImportService;
use App\Services\LoanDisbursementService;
use App\Services\LoanQuotationService;
use App\Services\PaymentSubmissionService;
use App\Services\RepaymentReversalService;
use Carbon\CarbonImmutable;
use Database\Seeders\AccountingPeriodSeeder;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\ProfessionalLoanProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

test('customers can find payment forms from the dashboard and payments menu without seeing other accounts', function (): void {
    $loan = financeTestLoan($this);
    $otherLoan = Loan::factory()->create();
    $this->actingAs($this->customer, 'customer')->get(route('portal.dashboard'))
        ->assertOk()->assertSee('Add payment')->assertSee(route('portal.payments.index'));
    $this->get(route('portal.payments.index'))->assertOk()
        ->assertSee(route('portal.loans.show', $loan).'#payment-form', false)
        ->assertSee('Assurance is included in loan installments')
        ->assertDontSee($otherLoan->loan_number);
    $this->get(route('portal.loans.show', $loan))->assertOk()->assertSee('id="payment-form"', false)->assertSee('Submit payment for approval');
    $loan->update(['status' => LoanStatus::Settled]);
    $this->get(route('portal.payments.index'))->assertOk()->assertDontSee($loan->loan_number)->assertSee('No loans are currently accepting repayments');
});

test('staff payment and import shortcuts respect permissions and import appears in navigation', function (): void {
    $response = $this->actingAs($this->staff)->get(route('dashboard'))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//nav[@aria-label="Primary navigation"]//a[@href="'.route('loan-imports.index').'"]')->length)->toBe(1);
    foreach (['payment-submissions.index', 'loan-imports.index'] as $route) {
        $url = route($route);
        expect($xpath->query('//section[contains(@class,"quick-panel")]//a[@href="'.$url.'"]')->length)->toBe(1)
            ->and($xpath->query('//section[@aria-label="Key performance indicators"]//a[@href="'.$url.'"]')->length)->toBe(0);
    }
    $this->staff->revokePermissionTo(['repayment.approve', 'loan.import']);
    $this->get(route('dashboard'))->assertOk()->assertDontSee('Review customer payments')->assertDontSee('Import historical loans');
});

beforeEach(function (): void {
    $this->seed([SystemSettingSeeder::class, ChartOfAccountSeeder::class, AccountingPeriodSeeder::class, RolePermissionSeeder::class]);
    Storage::fake('local');
    $this->staff = User::factory()->create();
    $this->staff->givePermissionTo(['repayment.approve', 'loan.import', 'loan.view', 'dashboard.view', 'dashboard.executive', 'loan-product.version.create', 'loan-product.view']);
    $this->customer = Customer::factory()->create(['branch_id' => $this->staff->branch_id]);
    $this->version = LoanProductVersion::factory()->create(['minimum_amount' => 20000]);
    $this->application = LoanApplication::factory()->create([
        'branch_id' => $this->staff->branch_id, 'customer_id' => $this->customer->id,
        'loan_product_version_id' => $this->version->id, 'approved_amount' => 50000, 'requested_amount' => 50000,
        'status' => LoanApplicationStatus::Approved,
    ]);
});

function financeTestLoan(object $test): Loan
{
    $loan = Loan::factory()->create(['branch_id' => $test->staff->branch_id, 'customer_id' => $test->customer->id, 'loan_application_id' => $test->application->id, 'principal_amount' => 50000, 'outstanding_principal' => 50000]);
    LoanInstallment::factory()->for($loan)->create(['principal_due' => 50000, 'interest_due' => 5000]);

    return $loan;
}

/** @return array<string, mixed> */
function financeImportRow(object $test): array
{
    return array_combine(HistoricalLoanImportService::HEADERS, ['OLD-001', $test->customer->customer_number, $test->version->product->code, 1, 50000, 40000, 10000, '2025-01-01', 20000, 'Active']) + ['_row' => 2];
}

test('assurance defaults to three percent and optional processing fees reduce only payout', function (): void {
    $quote = app(LoanQuotationService::class)->quote($this->version->fresh(), 50000, 1, 1000000, 0, 0);
    expect($quote['assurance_amount'])->toBe(1500.0)->and($quote['estimated_net_disbursement'])->toBe(50000.0);
    $this->version->fees()->create(['name' => 'Processing fee', 'calculation_method' => 'Fixed', 'value' => 10000, 'collection_point' => 'Disbursement', 'is_financed' => false]);
    $quote = app(LoanQuotationService::class)->quote($this->version->fresh(), 50000, 1, 1000000, 0, 0);
    expect($quote['estimated_net_disbursement'])->toBe(40000.0)->and($quote['principal'])->toBe(50000.0)->and($quote['total_customer_cost'])->toBe($quote['total_repayment'] + 10000);
});

test('disbursement deducts processing once and schedules assurance without advance payment', function (): void {
    $this->application->update(['requested_amount' => 500000, 'approved_amount' => 500000, 'requested_term' => 3]);
    $this->version->fees()->create(['name' => 'Processing fee', 'calculation_method' => 'Fixed', 'value' => 10000, 'collection_point' => 'Disbursement', 'is_financed' => false]);
    $service = app(LoanDisbursementService::class);
    $disbursement = $service->prepare($this->application, $this->staff, 'Bank');
    expect($disbursement->net_amount)->toBe('490000.00')->and($disbursement->gross_amount)->toBe('500000.00');
    $authorizer = User::factory()->create(['branch_id' => $this->staff->branch_id]);
    $loan = $service->authorize($disbursement, $authorizer, CarbonImmutable::today()->addMonth());
    $quote = app(LoanQuotationService::class)->quote($this->version->fresh(), 500000, 3, 1000000, 0, 0);
    expect((float) $loan->installments()->sum('fees_due'))->toBe(15000.0)
        ->and((float) $loan->installments()->sum('principal_due'))->toBe(500000.0)
        ->and((float) $loan->installments()->sum('interest_due'))->toBe($quote['total_interest'])
        ->and($loan->installments->sum(fn ($row) => (float) $row->principal_due + (float) $row->interest_due + (float) $row->fees_due))->toBe($quote['total_repayment']);
    expect(app(DashboardMetricsService::class)->summary($this->staff->branch_id)['loanIncome'])->toBe(10000.0);
});

test('customer payment remains pending until authorized approval and cannot post twice', function (): void {
    $loan = financeTestLoan($this);
    $this->actingAs($this->customer, 'customer')->post(route('portal.payments.store', $loan), [
        'amount' => 15000, 'payment_method' => 'Bank', 'external_reference' => 'BANK-001', 'paid_at' => today()->toDateString(),
        'attachment' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    $submission = PaymentSubmission::firstOrFail();
    expect($loan->fresh()->outstanding_principal)->toBe('50000.00')->and($loan->repayments()->count())->toBe(0)->and($submission->status)->toBe('Pending');
    Storage::disk('local')->assertExists($submission->attachment_path);
    $this->actingAs($this->staff)->post(route('payment-submissions.review', $submission), ['decision' => 'Approved'])->assertSessionHasNoErrors();
    expect($loan->fresh()->outstanding_principal)->toBe('40000.00')->and($submission->fresh()->status)->toBe('Approved');
    $this->post(route('payment-submissions.review', $submission), ['decision' => 'Approved'])->assertSessionHasErrors('payment');
    expect($loan->repayments()->count())->toBe(1);
    $metrics = app(DashboardMetricsService::class)->summary($this->staff->branch_id);
    expect($metrics['principalRecovered'])->toBe(10000.0)->and($metrics['loanIncome'])->toBe(5000.0)->and($metrics['totalRepaid'])->toBe(15000.0);
    $reverser = User::factory()->create(['branch_id' => $this->staff->branch_id]);
    app(RepaymentReversalService::class)->reverse($submission->fresh()->repayment, $reverser, 'Wrong customer payment reference');
    $metrics = app(DashboardMetricsService::class)->summary($this->staff->branch_id);
    expect($metrics['principalRecovered'])->toBe(0.0)->and($metrics['loanIncome'])->toBe(0.0)->and($metrics['collectedToday'])->toBe(0.0);
});

test('customer payment evidence attachment is optional', function (): void {
    $loan = financeTestLoan($this);

    $this->actingAs($this->customer, 'customer')->post(route('portal.payments.store', $loan), [
        'amount' => 1000,
        'payment_method' => 'Bank',
        'external_reference' => 'BANK-NO-FILE',
        'paid_at' => today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $submission = PaymentSubmission::firstOrFail();

    expect($submission->attachment_path)->toBeNull();

    $this->actingAs($this->staff)->get(route('payment-submissions.index'))
        ->assertOk()
        ->assertSee('No payment evidence attached.');
    $this->get(route('payment-submissions.attachment', $submission))->assertNotFound();
});

test('transaction sequence skips cash transaction references', function (): void {
    CashTransaction::query()->create([
        'branch_id' => $this->staff->branch_id,
        'transaction_number' => 'TRX-'.now()->year.'-00000001',
        'type' => 'Manual',
        'direction' => 'In',
        'amount' => 1000,
        'source_type' => User::class,
        'source_id' => $this->staff->id,
        'status' => 'Posted',
        'created_by' => $this->staff->id,
        'posted_at' => now(),
    ]);

    expect(NumberSequence::issue('transaction'))->toBe('TRX-'.now()->year.'-00000002');
});

test('journal sequence skips existing journal entry references', function (): void {
    JournalEntry::query()->create([
        'branch_id' => $this->staff->branch_id,
        'journal_number' => 'JV-'.now()->year.'-00000001',
        'posting_date' => today(),
        'source_type' => User::class,
        'source_id' => $this->staff->id,
        'description' => 'Existing journal',
        'status' => 'Posted',
        'created_by' => $this->staff->id,
        'posted_by' => $this->staff->id,
        'posted_at' => now(),
    ]);

    expect(NumberSequence::issue('journal'))->toBe('JV-'.now()->year.'-00000002');
});

test('review permissions branch boundaries and evidence ownership are enforced', function (): void {
    $loan = financeTestLoan($this);
    $submission = PaymentSubmission::factory()->create(['loan_application_id' => $this->application->id, 'loan_id' => $loan->id, 'purpose' => 'Repayment']);
    $outsider = User::factory()->create();
    $this->actingAs($outsider)->post(route('payment-submissions.review', $submission), ['decision' => 'Approved'])->assertForbidden();
    $outsider->givePermissionTo('repayment.approve');
    $this->post(route('payment-submissions.review', $submission), ['decision' => 'Approved'])->assertNotFound();
    $this->get(route('payment-submissions.attachment', $submission))->assertNotFound();
    $otherCustomer = Customer::factory()->create();
    $this->actingAs($otherCustomer, 'customer')->get(route('portal.payments.attachment', $submission))->assertNotFound();
    $this->post(route('portal.payments.store', $loan), [])->assertNotFound();
    expect($submission->fresh()->status)->toBe('Pending')->and($loan->repayments()->count())->toBe(0);
});

test('rejection and overpayment do not change financial balances', function (): void {
    $loan = financeTestLoan($this);
    $submission = PaymentSubmission::factory()->create(['loan_application_id' => $this->application->id, 'loan_id' => $loan->id, 'purpose' => 'Repayment', 'amount' => 60000]);
    $this->actingAs($this->staff)->post(route('payment-submissions.review', $submission), ['decision' => 'Approved'])->assertSessionHasErrors('payment');
    expect($submission->fresh()->status)->toBe('Pending')->and($loan->repayments()->count())->toBe(0);
    $this->post(route('payment-submissions.review', $submission), ['decision' => 'Rejected'])->assertSessionHasErrors('review_notes');
    $this->post(route('payment-submissions.review', $submission), ['decision' => 'Rejected', 'review_notes' => 'No matching bank payment.'])->assertSessionHasNoErrors();
    expect($submission->fresh()->status)->toBe('Rejected')->and($loan->fresh()->outstanding_principal)->toBe('50000.00');
});

test('duplicate references and unsafe evidence are rejected', function (): void {
    $loan = financeTestLoan($this);
    PaymentSubmission::factory()->create(['loan_application_id' => $this->application->id, 'external_reference' => 'BANK-001']);
    $data = ['amount' => 1000, 'payment_method' => 'Bank', 'external_reference' => 'bank-001', 'paid_at' => today()->toDateString(), 'attachment' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
    $this->actingAs($this->customer, 'customer')->post(route('portal.payments.store', $loan), $data)->assertSessionHasErrors('external_reference');
    $data['attachment'] = UploadedFile::fake()->create('script.html', 10, 'text/html');
    $this->post(route('portal.payments.store', $loan), $data)->assertSessionHasErrors('attachment');
    expect(PaymentSubmission::count())->toBe(1)->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('historical import preserves balances and rejects duplicates and invalid rows atomically', function (): void {
    $row = financeImportRow($this);
    $service = app(HistoricalLoanImportService::class);
    $invalid = $row;
    $invalid['loan_number'] = 'OLD-002';
    $invalid['total_paid'] = 60000;
    expect(fn () => $service->import([$row, $invalid], $this->staff))->toThrow(ValidationException::class);
    expect(Loan::count())->toBe(0);
    expect($service->import([$row], $this->staff))->toBe(1);
    $loan = Loan::firstOrFail();
    expect($loan->outstanding_principal)->toBe('30000.00')->and($loan->repayments()->count())->toBe(0);
    $metrics = app(DashboardMetricsService::class)->summary($this->staff->branch_id);
    expect($metrics['cashDisbursed'])->toBe(40000.0)->and($metrics['loanIncome'])->toBe(16500.0)->and($metrics['principalRecovered'])->toBe(20000.0);
    expect(fn () => $service->import([$row], $this->staff))->toThrow(ValidationException::class);
    expect(Loan::count())->toBe(1);
});

test('spreadsheet preview saves nothing until confirmation and screens render', function (): void {
    $row = financeImportRow($this);
    unset($row['_row']);
    $csv = implode(',', HistoricalLoanImportService::HEADERS)."\n".implode(',', $row)."\n";
    $this->actingAs($this->staff)->get(route('loan-imports.index'))->assertOk();
    $this->get(route('loan-imports.template'))->assertOk()->assertDownload('historical-loans-template.xlsx');
    $this->post(route('loan-imports.store'), ['file' => UploadedFile::fake()->createWithContent('loans.csv', $csv)])->assertOk()->assertSee('Confirm import');
    expect(Loan::count())->toBe(0);
    $token = session('loan_import_preview.token');
    $this->post(route('loan-imports.store'), ['action' => 'confirm', 'token' => $token])->assertSessionHasNoErrors()->assertRedirect(route('loans.index'));
    expect(Loan::count())->toBe(1);
    $this->post(route('loan-imports.store'), ['action' => 'confirm', 'token' => $token])->assertStatus(422);
    $this->get(route('dashboard'))->assertOk()->assertSee('Net lending profit');
    $this->get(route('payment-submissions.index'))->assertOk();
    $this->actingAs($this->customer, 'customer')->get(route('portal.loans.show', Loan::firstOrFail()))->assertOk()->assertSee('Submit payment for approval');
    $this->get(route('portal.applications.show', $this->application))->assertOk()->assertDontSee('Record assurance payment')->assertSee('included in loan installments');
});

test('product version form creates optional deducted fees and assurance terms', function (): void {
    $data = ['minimum_amount' => 10000, 'maximum_amount' => 100000, 'minimum_term' => 1, 'maximum_term' => 12, 'term_unit' => 'Months', 'interest_method' => 'Flat', 'interest_rate_percent' => 10, 'rate_basis' => 'Whole Term', 'repayment_frequency' => 'Monthly', 'principal_grace_period' => 0, 'interest_grace_period' => 0, 'required_guarantors' => 0, 'maximum_installment_ratio_percent' => 50, 'effective_from' => today()->addDay()->toDateString(), 'assurance_percent' => 3, 'processing_fee' => 15000];
    $this->actingAs($this->staff)->post(route('loan-products.versions.store', $this->version->product), $data)->assertSessionHasNoErrors();
    $version = $this->version->product->versions()->latest('version')->first();
    expect((float) $version->assurance_percent)->toBe(3.0)->and($version->fees()->first()->collection_point)->toBe('Installment');
    $this->get(route('loan-products.show', $version->product))->assertOk()->assertSee('Processing fee for the whole loan');
});

test('a product can disable assurance and processing fees and never disburse negative cash', function (): void {
    $this->version->update(['assurance_percent' => 0]);
    $quote = app(LoanQuotationService::class)->quote($this->version, 50000, 1, 1000000, 0, 0);
    expect($quote['assurance_amount'])->toBe(0.0)->and($quote['total_fees'])->toBe(0.0);
    $this->version->fees()->create(['name' => 'Processing fee', 'calculation_method' => 'Fixed', 'value' => 50000, 'collection_point' => 'Disbursement', 'is_financed' => false]);
    expect(fn () => app(LoanDisbursementService::class)->prepare($this->application, $this->staff, 'Bank'))->toThrow(DomainException::class, 'less than');
    $this->version->fees()->delete();
    expect(app(LoanDisbursementService::class)->prepare($this->application, $this->staff, 'Bank')->net_amount)->toBe('50000.00');
});

test('previous assurance approval and loan authorization post the exact payout and income', function (): void {
    $submission = PaymentSubmission::factory()->create(['loan_application_id' => $this->application->id, 'customer_id' => $this->customer->id, 'amount' => 1500]);
    app(PaymentSubmissionService::class)->review($submission, $this->staff, 'Approved', null);
    $this->version->fees()->create(['name' => 'Processing fee', 'calculation_method' => 'Fixed', 'value' => 10000, 'collection_point' => 'Disbursement', 'is_financed' => false]);
    $service = app(LoanDisbursementService::class);
    $disbursement = $service->prepare($this->application, $this->staff, 'Bank');
    $authorizer = User::factory()->create(['branch_id' => $this->staff->branch_id]);
    $loan = $service->authorize($disbursement, $authorizer, CarbonImmutable::today()->addMonth());
    expect($loan->principal_amount)->toBe('50000.00')->and((float) $loan->installments()->sum('principal_due'))->toBe(50000.0);
    $metrics = app(DashboardMetricsService::class)->summary($this->staff->branch_id);
    expect($metrics['cashDisbursed'])->toBe(40000.0)->and($metrics['loanIncome'])->toBe(11500.0);
});

test('xlsx import handles Excel dates and rejects cross branch and inconsistent schedules', function (): void {
    $row = financeImportRow($this);
    unset($row['_row']);
    $row['disbursed_at'] = Date::PHPToExcel(new DateTimeImmutable('2025-01-01'));
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([HistoricalLoanImportService::HEADERS, array_values($row)], null, 'A1', true);
    $path = storage_path('app/private/test-historical.xlsx');
    (new Xlsx($book))->save($path);
    try {
        $service = app(HistoricalLoanImportService::class);
        $rows = $service->read($path);
        expect($rows[0]['disbursed_at'])->toBe('2025-01-01');
        expect($service->validate($rows, $this->staff))->toHaveCount(1);
        $outsider = User::factory()->create();
        expect(fn () => $service->validate($rows, $outsider))->toThrow(ValidationException::class);
        $rows[0]['principal_due'] = 45000;
        expect(fn () => $service->validate($rows, $this->staff))->toThrow(ValidationException::class, 'principal');
    } finally {
        unlink($path);
        $book->disconnectWorksheets();
    }
});

test('dashboard scopes lending and deducts expenses from profit separately from capital', function (): void {
    $loan = financeTestLoan($this);
    $loan->installments()->update(['interest_paid' => 5000]);
    Loan::factory()->create(['principal_amount' => 900000]);
    ChartOfAccount::factory()->create(['code' => '3000', 'name' => 'Owner capital', 'type' => 'Equity', 'normal_balance' => 'Credit', 'is_active' => true, 'is_control_account' => false, 'allows_manual_posting' => true]);
    $posting = app(AccountingPostingService::class);
    $posting->post($this->staff, 'Capital contribution', User::class, $this->staff->id, [['account_code' => '1000', 'debit' => 100000], ['account_code' => '3000', 'credit' => 100000]], $this->staff->branch_id);
    $posting->post($this->staff, 'Operating expense', User::class, $this->staff->id, [['account_code' => '5000', 'debit' => 2000], ['account_code' => '1000', 'credit' => 2000]], $this->staff->branch_id);
    $metrics = app(DashboardMetricsService::class)->summary($this->staff->branch_id);
    expect($metrics['totalPrincipal'])->toBe(50000.0)->and($metrics['netLendingProfit'])->toBe(3000.0)->and($metrics['recordedCapital'])->toBe(100000.0)->and($metrics['capitalWithProfit'])->toBe(103000.0);
});

test('cash submissions require an open till and a global reviewer posts to the loan branch', function (): void {
    $loan = financeTestLoan($this);
    $submission = PaymentSubmission::factory()->create(['loan_application_id' => $this->application->id, 'loan_id' => $loan->id, 'purpose' => 'Repayment', 'payment_method' => 'Cash', 'amount' => 1000]);
    $service = app(PaymentSubmissionService::class);
    expect(fn () => $service->review($submission, $this->staff, 'Approved', null))->toThrow(DomainException::class, 'till');
    expect($submission->fresh()->status)->toBe('Pending')->and($loan->repayments()->count())->toBe(0);
    $this->staff->tillSessions()->create(['branch_id' => $this->staff->branch_id, 'opening_balance' => 0, 'expected_balance' => 0, 'status' => 'Open', 'opened_at' => now()]);
    $service->review($submission, $this->staff, 'Approved', null);
    expect((float) $this->staff->tillSessions()->first()->expected_balance)->toBe(1000.0);
    $global = User::factory()->create(['branch_id' => null]);
    $global->givePermissionTo('repayment.approve');
    $bankSubmission = PaymentSubmission::factory()->create(['loan_application_id' => $this->application->id, 'loan_id' => $loan->id, 'purpose' => 'Repayment', 'amount' => 1000]);
    $service->review($bankSubmission, $global, 'Approved', null);
    $this->assertDatabaseHas('cash_transactions', ['source_type' => Repayment::class, 'source_id' => $bankSubmission->fresh()->repayment_id, 'branch_id' => $loan->branch_id]);
});

test('historical loans with paid principal stay payable until all remaining charges are cleared', function (): void {
    $row = financeImportRow($this);
    $row['principal_paid'] = 50000;
    $row['interest_paid'] = 0;
    app(HistoricalLoanImportService::class)->import([$row], $this->staff);
    $loan = Loan::firstOrFail();
    $submission = PaymentSubmission::factory()->create(['loan_application_id' => $loan->loan_application_id, 'loan_id' => $loan->id, 'purpose' => 'Repayment', 'amount' => 1000]);
    app(PaymentSubmissionService::class)->review($submission, $this->staff, 'Approved', null);
    expect($loan->fresh()->status)->not->toBe(LoanStatus::Settled);
    $rest = PaymentSubmission::factory()->create(['loan_application_id' => $loan->loan_application_id, 'loan_id' => $loan->id, 'purpose' => 'Repayment', 'amount' => 4000]);
    app(PaymentSubmissionService::class)->review($rest, $this->staff, 'Approved', null);
    expect($loan->fresh()->status)->toBe(LoanStatus::Settled);
});

test('duplicate processing fees cannot be added to an unused version', function (): void {
    $this->staff->givePermissionTo('loan-product.fee.manage');
    $version = LoanProductVersion::factory()->create();
    $version->fees()->create(['name' => 'Processing Fee', 'calculation_method' => 'Fixed', 'value' => 10000, 'collection_point' => 'Disbursement', 'is_financed' => false]);
    $this->actingAs($this->staff)->post(route('loan-product-versions.fees.store', $version), [
        'name' => ' processing fee ', 'calculation_method' => 'Fixed', 'value' => 10000, 'collection_point' => 'Disbursement',
    ])->assertSessionHasErrors('name');
    expect($version->fees()->count())->toBe(1);
});

test('customers cannot submit separate assurance payments', function (): void {
    $this->actingAs($this->customer, 'customer')->post(route('portal.assurance.store', $this->application), [
        'amount' => 1500, 'payment_method' => 'Bank', 'external_reference' => 'ASSURANCE-NEW', 'paid_at' => today()->toDateString(),
    ])->assertStatus(422);
    expect(PaymentSubmission::count())->toBe(0);
});

test('standard product seeding adds the fixed distributed processing fee', function (): void {
    $this->seed(ProfessionalLoanProductSeeder::class);
    $product = LoanProduct::where('code', 'STANDARD-MICRO')->firstOrFail();
    $fee = $product->versions()->firstOrFail()->fees()->where('name', 'Processing fee')->firstOrFail();
    expect((float) $fee->value)->toBe(15000.0)
        ->and($fee->calculation_method)->toBe('Fixed')
        ->and($fee->collection_point)->toBe('Installment')
        ->and($fee->is_financed)->toBeTrue();
});
