<?php

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerActivityNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('staff resets customer portal password and sends notification', function (): void {
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');
    $customer = Customer::factory()->create(['branch_id' => $staff->branch_id, 'status' => CustomerStatus::Active, 'verified_at' => now(), 'email' => 'customer@example.test', 'password' => 'OldPassword1']);
    $oldPassword = $customer->password;

    $this->actingAs($staff)->post(route('customers.portal-password.reset', $customer), ['reset_mode' => 'generate'])->assertSessionHasNoErrors();

    expect($customer->fresh()->password)->not->toBe($oldPassword)
        ->and($customer->fresh()->must_change_password)->toBeTrue();
    Notification::assertSentTo($customer, CustomerActivityNotification::class, fn ($notification) => $notification->type === 'portal_password_reset');
});

test('customer with temporary password must replace it before dashboard access', function (): void {
    $customer = Customer::factory()->create(['status' => CustomerStatus::Active, 'verified_at' => now(), 'email' => 'portal@example.test', 'password' => 'Temporary1', 'must_change_password' => true]);

    $this->post(route('portal.login.store'), ['email' => 'portal@example.test', 'password' => 'Temporary1'])->assertRedirect(route('portal.password.edit'));
    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.password.edit'));
    $this->put(route('portal.password.update'), ['password' => 'Permanent123', 'password_confirmation' => 'Permanent123'])->assertRedirect(route('portal.dashboard'));

    expect($customer->fresh()->must_change_password)->toBeFalse();
});
