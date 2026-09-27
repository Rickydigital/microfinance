<?php

use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\Repayment;
use App\Models\User;
use App\Services\LoanQuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('whole term interest basis applies the configured percentage once across the term', function (): void {
    $wholeTerm = LoanProductVersion::factory()->create(['interest_method' => 'Flat', 'interest_rate' => 0.24, 'rate_basis' => 'Whole Term', 'repayment_frequency' => 'Monthly']);
    $annual = LoanProductVersion::factory()->create(['interest_method' => 'Flat', 'interest_rate' => 0.24, 'rate_basis' => 'Annual', 'repayment_frequency' => 'Monthly']);
    $service = app(LoanQuotationService::class);

    $wholeTermQuote = $service->quote($wholeTerm->load('fees'), 200000, 6, 500000, 100000, 0);
    $annualQuote = $service->quote($annual->load('fees'), 200000, 6, 500000, 100000, 0);

    expect($wholeTermQuote['total_interest'])->toBe(48000.0)
        ->and($annualQuote['total_interest'])->toBe(24000.0)
        ->and($wholeTermQuote['periodic_rate'])->toBe(0.04);
});

test('repayment receipt downloads through the shared a4 pdf renderer', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('System Administrator');
    $loan = Loan::factory()->create();
    $user->update(['branch_id' => $loan->branch_id]);
    $repayment = Repayment::query()->create(['loan_id' => $loan->id, 'receipt_number' => 'RCT-PDF-1', 'amount' => 1000, 'status' => 'Posted', 'payment_method' => 'Cash', 'paid_at' => now(), 'received_by' => $user->id]);

    $this->actingAs($user)->get(route('repayments.pdf', $repayment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('shared pdf layout uses the reference letterhead typography and tables', function (): void {
    $html = view('pdf.report', [
        'report' => 'portfolio',
        'definition' => ['label' => 'Portfolio', 'group_label' => 'Loan portfolio summary'],
        'metrics' => ['active_loans' => 5, 'outstanding_principal' => 500000, 'collections' => 20000, 'open_cases' => 0, 'debits' => 500000, 'credits' => 500000],
    ])->render();
    expect($html)->toContain('Times New Roman', 'institution-header', 'document-footer', 'pdf-watermark', 'Portfolio Report');
    $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();
    expect($pdf)->toStartWith('%PDF-');
    if (getenv('PDF_PREVIEW_PATH')) {
        file_put_contents(getenv('PDF_PREVIEW_PATH'), $pdf);
    }
});

test('loan dossier renders a multi page repayment schedule with financed assurance', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('System Administrator');
    $application = LoanApplication::factory()->create(['branch_id' => $user->branch_id, 'requested_amount' => 500000, 'requested_term' => 24]);
    $response = $this->actingAs($user)->get(route('loan-applications.pdf', $application))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    if (getenv('PDF_PREVIEW_PATH')) {
        file_put_contents(getenv('PDF_PREVIEW_PATH').'.dossier.pdf', $response->getContent());
    }
});
