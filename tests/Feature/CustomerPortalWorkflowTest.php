<?php

use App\CustomerStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\LoanProductVersion;
use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('customer portal authentication redirects stay on the customer portal', function (): void {
    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
});

test('self registered customer cannot login until staff verification', function (): void {
    Notification::fake();
    $this->seed(SystemSettingSeeder::class);
    CustomerType::factory()->create(['code' => 'Individual', 'name' => 'Individual', 'is_active' => true]);
    $branch = Branch::factory()->create(['is_active' => true]);

    $this->post(route('portal.register.store'), ['branch_id' => $branch->id, 'type' => 'Individual', 'first_name' => 'Asha', 'last_name' => 'Juma', 'gender' => 'Female', 'date_of_birth' => '1995-01-01', 'phone' => '255700123456', 'email' => 'asha@example.test', 'password' => 'Secure1234', 'password_confirmation' => 'Secure1234'])->assertRedirect(route('portal.login'));
    $customer = Customer::query()->where('email', 'asha@example.test')->firstOrFail();

    expect($customer->status)->toBe(CustomerStatus::PendingVerification)
        ->and($customer->portal_registered_at)->not->toBeNull();
    $this->post(route('portal.login.store'), ['email' => 'asha@example.test', 'password' => 'Secure1234'])->assertSessionHasErrors('email');

    $customer->update(['status' => CustomerStatus::Active, 'verified_at' => now()]);
    $this->post(route('portal.login.store'), ['email' => 'asha@example.test', 'password' => 'Secure1234'])->assertRedirect(route('portal.dashboard'));
    $this->assertAuthenticatedAs($customer, 'customer');
});

test('verified customer submits an affordable application into staff workflow', function (): void {
    Notification::fake();
    $this->seed(SystemSettingSeeder::class);
    $officer = User::factory()->create();
    $customer = Customer::factory()->create(['branch_id' => $officer->branch_id, 'assigned_officer_id' => $officer->id, 'status' => CustomerStatus::Active, 'verified_at' => now(), 'password' => 'Secure1234']);
    $customer->economicProfiles()->create(['occupation' => 'Trader', 'monthly_income' => 2000000, 'monthly_expenses' => 300000, 'existing_debt_payment' => 0, 'income_source' => 'Business', 'effective_from' => today()]);
    $version = LoanProductVersion::factory()->create(['minimum_amount' => 100000, 'maximum_amount' => 1000000, 'maximum_installment_ratio' => 0.8]);

    $this->actingAs($customer, 'customer')->post(route('portal.applications.store'), ['loan_product_version_id' => $version->id, 'requested_amount' => 500000, 'requested_term' => 12, 'purpose' => 'Purchase additional business inventory'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('loan_applications', ['customer_id' => $customer->id, 'portal_submitted_by' => $customer->id, 'source' => 'customer_portal', 'status' => 'Submitted']);
});
