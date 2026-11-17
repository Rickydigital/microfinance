<?php

use App\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\CustomerEconomicProfile;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use App\Models\ProductFee;
use App\Models\Repayment;
use Database\Seeders\BranchSeeder;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\MyCashHistoricalLoansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(InstitutionSeeder::class);
    $this->seed(BranchSeeder::class);
});

it('seeds the historical workbook products customers and applications idempotently', function (): void {
    $this->seed(MyCashHistoricalLoansSeeder::class);
    $this->seed(MyCashHistoricalLoansSeeder::class);

    expect(LoanProduct::query()->where('code', 'like', 'MYCASH-%')->count())->toBe(3)
        ->and(LoanProduct::query()->whereIn('name', ['Mkopo Mdogo', 'Mkopo wa Biashara', 'Mkopo wa Dharura'])->count())->toBe(3)
        ->and((float) LoanProduct::query()->where('name', 'Mkopo Mdogo')->firstOrFail()->latestVersion->interest_rate)->toBe(0.03)
        ->and(LoanProductVersion::query()->whereHas('product', fn ($query) => $query->where('code', 'like', 'MYCASH-%'))->where('assurance_percent', 0)->count())->toBe(3)
        ->and(ProductFee::query()->whereHas('productVersion.product', fn ($query) => $query->where('code', 'like', 'MYCASH-%'))->where('calculation_method', 'Fixed')->where('value', 15000)->count())->toBe(3)
        ->and(ProductFee::query()->whereHas('productVersion.product', fn ($query) => $query->where('code', 'like', 'MYCASH-%'))->where('collection_point', 'Installment')->where('is_financed', true)->count())->toBe(3)
        ->and(Customer::query()->where('customer_number', 'like', 'MYC-CUS-%')->count())->toBeGreaterThan(20)
        ->and(LoanApplication::query()->where('application_number', 'like', 'MYC-2026-%')->count())->toBe(41)
        ->and(LoanApplication::query()->where('status', LoanApplicationStatus::ConvertedToLoan)->count())->toBe(41)
        ->and(LoanApplication::query()->where('status', LoanApplicationStatus::Approved)->count())->toBe(0)
        ->and(Loan::query()->where('loan_number', 'like', 'MYC-LN-2026-%')->count())->toBe(41)
        ->and(LoanDisbursement::query()->where('disbursement_number', 'like', 'MYC-DSB-2026-%')->where('status', 'Disbursed')->count())->toBe(41)
        ->and(Loan::query()->where('status', 'Settled')->where('outstanding_principal', 0)->count())->toBe(24)
        ->and(Repayment::query()->where('receipt_number', 'like', 'MYC-RCP-2026-%')->where('status', 'Posted')->count())->toBe(24)
        ->and(LoanInstallment::query()->where('status', 'Paid')->count())->toBeGreaterThan(20);
});

it('preserves representative workbook values', function (): void {
    $this->seed(MyCashHistoricalLoansSeeder::class);

    $firstLoan = LoanApplication::query()->where('application_number', 'MYC-2026-0001')->firstOrFail();
    $finalLoan = LoanApplication::query()->where('application_number', 'MYC-2026-0041')->firstOrFail();

    expect((float) $firstLoan->requested_amount)->toBe(1500000.0)
        ->and($firstLoan->requested_term)->toBe(6)
        ->and($firstLoan->customer->first_name)->toBe('Kondo')
        ->and((float) $firstLoan->productVersion->interest_rate)->toBe(0.05)
        ->and((float) $finalLoan->requested_amount)->toBe(2200000.0)
        ->and($finalLoan->customer->first_name)->toBe('Rosemary')
        ->and((float) $finalLoan->productVersion->interest_rate)->toBe(0.1);
});

it('uses the published Bootstrap 5 pagination view globally', function (): void {
    $html = (new LengthAwarePaginator(range(1, 20), 41, 20, 1, ['path' => '/loans']))->links()->toHtml();

    expect($html)->toContain('pagination')
        ->and($html)->toContain('page-item')
        ->and(resource_path('views/vendor/pagination/bootstrap-5.blade.php'))->toBeFile();
});

it('shows the interactive calculator with the Swahili products in the customer portal', function (): void {
    $this->seed(MyCashHistoricalLoansSeeder::class);
    $customer = Customer::query()->firstOrFail();
    CustomerEconomicProfile::query()->create(['customer_id' => $customer->id, 'monthly_income' => 2000000, 'monthly_expenses' => 500000, 'existing_debt_payment' => 0, 'effective_from' => today()]);

    $this->actingAs($customer, 'customer')->get(route('portal.applications.create'))
        ->assertOk()
        ->assertSee('Loan calculator')
        ->assertSee('Mkopo Mdogo')
        ->assertSee('Mkopo wa Biashara')
        ->assertSee('Mkopo wa Dharura')
        ->assertSee('id="est-installment"', false);
});
