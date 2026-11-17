<?php

use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Collateral;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\IdentificationType;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\NumberSequence;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff screens load the shared design system without replacing staff navigation', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('app-assets/css/staff-design-system.css', false)
        ->assertSee('<body class="sidebar-collapsed">', false)
        ->assertSee('class="sidebar"', false)
        ->assertSee('class="topbar"', false)
        ->assertDontSee('class="brand-text"', false)
        ->assertSee('At a glance')
        ->assertSee('Loan portfolio')
        ->assertSee('Released versus collected')
        ->assertSee('Performance overview')
        ->assertSee('Portfolio health')
        ->assertSee('data-dashboard-tab="overview"', false)
        ->assertSee('data-dashboard-tab="members"', false)
        ->assertSee('data-dashboard-tab="loans"', false)
        ->assertSee('Member growth')
        ->assertSee('New versus returning borrowers')
        ->assertSee('Loan application stages')
        ->assertSee('Reporting period')
        ->assertSee('These filters apply consistently to Overview, Members, and Loans.')
        ->assertSee('Disbursements by loan product')
        ->assertSee('Loan product insights')
        ->assertSee('Loan guarantor exposure')
        ->assertDontSee('Phone devices', false);
});

test('desktop sidebar expands on hover from its default icon rail', function (): void {
    $stylesheet = file_get_contents(public_path('app-assets/css/microfinance.css'));
    $javascript = file_get_contents(public_path('app-assets/js/microfinance.js'));

    expect($stylesheet)
        ->toContain('body.sidebar-collapsed .sidebar:hover')
        ->toContain('body.sidebar-collapsed .sidebar:focus-within')
        ->and($javascript)
        ->toContain("document.body.classList.add('sidebar-collapsed');")
        ->not->toContain('microfinance:sidebar-collapsed');
});

test('dashboard member and loan tabs render branch portfolio data', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');
    $customer = Customer::factory()->create(['branch_id' => $staff->branch_id, 'gender' => 'Female', 'date_of_birth' => now()->subYears(28)]);
    $application = LoanApplication::factory()->create(['branch_id' => $staff->branch_id, 'customer_id' => $customer->id]);
    Loan::factory()->create(['branch_id' => $staff->branch_id, 'customer_id' => $customer->id, 'loan_application_id' => $application->id, 'status' => LoanStatus::Active, 'principal_amount' => 1200000, 'outstanding_principal' => 800000, 'disbursed_at' => today()]);

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Active youth')
        ->assertSee('Female')
        ->assertSee('TZS 1,200,000.00')
        ->assertSee('Open loan book');

    $this->get(route('dashboard', ['period' => 'this_month', 'gender' => 'Female']))
        ->assertOk()
        ->assertSee('This month')
        ->assertSee('Female')
        ->assertSee('Disbursements by loan product');
});

test('staff authentication screens load the shared design system', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('app-assets/css/staff-design-system.css', false)
        ->assertSee('class="auth-shell"', false);
});

test('select2 dropdowns are attached outside cards so suggestions remain visible', function (): void {
    $javascript = file_get_contents(public_path('app-assets/js/microfinance.js'));
    $stylesheet = file_get_contents(public_path('app-assets/css/staff-design-system.css'));

    expect($javascript)
        ->toContain("select.closest('.modal, .register-modal')")
        ->toContain('var dropdownParent = modal.length ? modal : window.jQuery(document.body);')
        ->not->toContain("dropdownParent: select.closest('.card, .modal, form').first()")
        ->and($stylesheet)
        ->toContain('z-index: 1090;');
});

test('customer navigation contains guarantors and collateral', function (): void {
    $customerNavigation = collect(config('microfinance.navigation'))->firstWhere('label', 'Customer management');
    $securityNavigation = collect(config('microfinance.navigation'))->firstWhere('label', 'Collections & security');

    expect(collect($customerNavigation['items'])->pluck('route')->all())
        ->toContain('guarantors.index', 'collaterals.index')
        ->not->toContain('customers.create', 'customers.import.index')
        ->and(collect($securityNavigation['items'])->pluck('route')->all())
        ->not->toContain('guarantors.index', 'collaterals.index');
});

test('security registers provide create and import modals with live filters and samples', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');

    $this->actingAs($staff)->get(route('guarantors.index'))->assertOk()->assertSee('Create guarantor')->assertSee('href="#create-guarantor"', false)->assertSee('data-live-filter', false)->assertSee('Search guarantors')->assertSee('Download import sample');
    $this->get(route('collaterals.index'))->assertOk()->assertSee('Create collateral')->assertSee('href="#create-collateral"', false)->assertSee('data-live-filter', false)->assertSee('Search collateral')->assertSee('Download import sample');
    $this->get(route('security-registers.import.template', 'guarantors'))->assertOk()->assertDownload('guarantors-import-sample.csv');
    $this->get(route('security-registers.import.template', 'collaterals'))->assertOk()->assertDownload('collaterals-import-sample.csv');
});

test('staff forms prevent duplicate submissions and show progress', function (): void {
    $javascript = file_get_contents(public_path('app-assets/js/microfinance.js'));

    expect($javascript)
        ->toContain('initializeFormSubmissionGuard();')
        ->toContain("form.dataset.submitting === 'true'")
        ->toContain('spinner-border spinner-border-sm')
        ->toContain('Submitting…');
});

test('guarantor and collateral registers use summary cards and action menus', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');

    $this->actingAs($staff)->get(route('guarantors.index'))
        ->assertOk()
        ->assertSee('Total guarantors');

    $this->actingAs($staff)->get(route('collaterals.index'))
        ->assertOk()
        ->assertSee('Total collateral');

    expect(file_get_contents(resource_path('views/guarantors/index.blade.php')))
        ->toContain('mdi-dots-vertical')
        ->toContain('popovertarget="guarantor-actions-')
        ->toContain('popover="auto"')
        ->toContain('href="#details-guarantor-')
        ->toContain('href="#attach-guarantor-')
        ->toContain('Signed guarantor consent received')
        ->toContain('.register-modal:target')
        ->toContain("classList.remove('modal','fade')")
        ->toContain('min-height:158px')
        ->toContain('event.target===overlay')
        ->toContain("input.value.replace(/,/g,'')")
        ->toContain('modal-footer .btn{min-width:130px')
        ->not->toContain('View customer')
        ->not->toContain("#action-{{ \$record->id }}').showModal()")
        ->and(file_get_contents(resource_path('views/collaterals/index.blade.php')))
        ->toContain('mdi-dots-vertical')
        ->toContain('popovertarget="collateral-actions-')
        ->toContain('popover="auto"')
        ->toContain('href="#details-collateral-')
        ->toContain('.register-modal:target')
        ->toContain("classList.remove('modal','fade')")
        ->toContain('min-height:158px')
        ->toContain('event.target===overlay')
        ->toContain("input.value.replace(/,/g,'')")
        ->toContain('modal-footer .btn{min-width:130px')
        ->not->toContain('View customer')
        ->not->toContain("#action-{{ \$record->id }}').showModal()");
});

test('staff can create guarantor and collateral register records', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');
    $customer = Customer::factory()->create(['branch_id' => $staff->branch_id]);
    IdentificationType::factory()->create(['code' => 'NIDA', 'name' => 'National ID', 'is_active' => true]);
    NumberSequence::query()->create(['key' => 'collateral', 'prefix' => 'COLL-', 'next_number' => 1, 'padding' => 6, 'reset_annually' => false]);

    $this->actingAs($staff)->post(route('guarantors.store'), [
        'principal_customer_id' => $customer->id,
        'first_name' => 'Amina',
        'last_name' => 'Mushi',
        'phone' => '0712345678',
        'identification_type' => 'NIDA',
        'identification_number' => 'NIDA-REG-001',
        'occupation' => 'Teacher',
        'monthly_income' => 900000,
    ])->assertRedirect();

    $this->post(route('collaterals.store'), [
        'customer_id' => $customer->id,
        'type' => 'Vehicle',
        'description' => 'Delivery vehicle',
        'ownership_reference' => 'REG-T-100',
        'market_value' => 15000000,
        'forced_sale_value' => 10000000,
        'valuer' => 'Approved Valuer',
        'valuation_date' => now()->toDateString(),
    ])->assertRedirect();

    $this->assertDatabaseHas('guarantors', ['principal_customer_id' => $customer->id, 'identification_number' => 'NIDA-REG-001', 'status' => 'Pending Verification']);
    $this->assertDatabaseHas('collaterals', ['customer_id' => $customer->id, 'collateral_number' => 'COLL-000001', 'status' => 'Proposed']);

    $guarantor = Guarantor::query()->where('identification_number', 'NIDA-REG-001')->firstOrFail();
    $collateral = Collateral::query()->where('collateral_number', 'COLL-000001')->firstOrFail();

    $this->patch(route('guarantors.update', $guarantor), [
        'principal_customer_id' => $customer->id,
        'first_name' => 'Amina Updated',
        'last_name' => 'Mushi',
        'phone' => '0712345678',
        'identification_type' => 'NIDA',
        'identification_number' => 'NIDA-REG-001',
        'occupation' => 'Teacher',
        'monthly_income' => 950000,
    ])->assertRedirect();
    $this->patch(route('collaterals.update', $collateral), [
        'customer_id' => $customer->id,
        'type' => 'Vehicle',
        'description' => 'Updated delivery vehicle',
        'ownership_reference' => 'REG-T-100',
        'market_value' => 16000000,
        'forced_sale_value' => 10000000,
        'valuer' => 'Approved Valuer',
        'valuation_date' => now()->toDateString(),
    ])->assertRedirect();
    $loanApplication = LoanApplication::factory()->create(['branch_id' => $staff->branch_id, 'customer_id' => $customer->id, 'status' => LoanApplicationStatus::Submitted, 'requested_amount' => 2000000]);
    $this->post(route('guarantors.verify', $guarantor))->assertRedirect();
    $this->post(route('guarantors.loans.attach', $guarantor), ['loan_application_id' => $loanApplication->id, 'guaranteed_amount' => 750000, 'consent_received' => 1, 'notes' => 'Signed in branch'])->assertRedirect();
    $this->assertDatabaseHas('loan_guarantors', ['loan_application_id' => $loanApplication->id, 'guarantor_id' => $guarantor->id, 'guaranteed_amount' => 750000, 'consent_received' => true]);

    $this->patch(route('guarantors.status', $guarantor), ['active' => false])->assertRedirect();
    $this->patch(route('collaterals.status', $collateral), ['active' => false])->assertRedirect();

    $this->assertDatabaseHas('guarantors', ['id' => $guarantor->id, 'first_name' => 'Amina Updated', 'status' => 'Inactive']);
    $this->assertDatabaseHas('collaterals', ['id' => $collateral->id, 'description' => 'Updated delivery vehicle', 'status' => 'Inactive']);
    $this->get(route('guarantors.index', ['search' => 'Amina Updated']))->assertOk()->assertSee('Amina Updated');
    $this->get(route('collaterals.index', ['search' => 'Updated delivery']))->assertOk()->assertSee('Updated delivery vehicle');
});
