<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\CustomerTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an authenticated employee can register a customer', function () {
    $user = User::factory()->create();
    $this->seed(RolePermissionSeeder::class);
    $user->assignRole('Customer KYC Officer');
    $this->seed(SystemSettingSeeder::class);
    $this->seed(CustomerTypeSeeder::class);

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'type' => 'Individual',
        'first_name' => 'Asha',
        'last_name' => 'Mushi',
        'phone' => '+255712345678',
        'gender' => 'Female',
    ]);

    $customer = Customer::query()->firstOrFail();
    $response->assertRedirect(route('customers.show', $customer));
    expect($customer->branch_id)->toBe($user->branch_id)
        ->and($customer->customer_number)->toBe('CUS-000001');
});
