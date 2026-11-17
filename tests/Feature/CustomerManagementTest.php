<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\CustomerTypeSeeder;
use Database\Seeders\IdentificationTypeSeeder;
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
    $this->seed(IdentificationTypeSeeder::class);

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'type' => 'Individual',
        'first_name' => 'Asha',
        'last_name' => 'Mushi',
        'phone' => '+255712345678',
        'gender' => 'Female',
        'identification_type' => 'NIDA',
        'identification_number' => '19900101123450000111',
        'economic' => [
            'occupation' => 'Trader',
            'income_source' => 'Retail business',
            'monthly_income' => 1000000,
            'monthly_expenses' => 300000,
            'existing_debt_payment' => 0,
        ],
    ]);

    $customer = Customer::query()->firstOrFail();
    $response->assertRedirect(route('customers.show', $customer));
    expect($customer->branch_id)->toBe($user->branch_id)
        ->and($customer->customer_number)->toBe('CUS-000001')
        ->and($customer->identifications()->firstOrFail()->verified_by)->toBe($user->id)
        ->and($customer->identifications()->firstOrFail()->verified_at)->not->toBeNull()
        ->and($customer->economicProfiles()->whereNull('effective_to')->exists())->toBeTrue();
});

test('editing a customer automatically verifies the submitted identification and saves the economic profile', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(CustomerTypeSeeder::class);
    $this->seed(IdentificationTypeSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Customer KYC Officer');
    $customer = Customer::factory()->create([
        'branch_id' => $user->branch_id,
        'identification_type' => null,
        'identification_number' => null,
    ]);

    $response = $this->actingAs($user)->put(route('customers.update', $customer), [
        'type' => 'Individual',
        'first_name' => $customer->first_name,
        'middle_name' => $customer->middle_name,
        'last_name' => $customer->last_name,
        'phone' => $customer->phone,
        'identification_type' => 'NIDA',
        'identification_number' => '19900101123450000112',
        'economic' => [
            'occupation' => 'Shop owner',
            'income_source' => 'General shop',
            'monthly_income' => 1500000,
            'monthly_expenses' => 450000,
            'existing_debt_payment' => 100000,
        ],
    ]);

    $response->assertRedirect(route('customers.show', $customer));

    $identification = $customer->identifications()->firstOrFail();
    $economicProfile = $customer->economicProfiles()->whereNull('effective_to')->firstOrFail();

    expect($identification->type)->toBe('NIDA')
        ->and($identification->number)->toBe('19900101123450000112')
        ->and($identification->verified_by)->toBe($user->id)
        ->and($identification->verified_at)->not->toBeNull()
        ->and($economicProfile->occupation)->toBe('Shop owner')
        ->and($economicProfile->income_source)->toBe('General shop');
});

test('a kyc officer sees a verify action for each pending customer document', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Customer KYC Officer');
    $customer = Customer::factory()->create([
        'branch_id' => $user->branch_id,
        'verified_at' => null,
    ]);
    $document = $customer->documents()->create([
        'type' => 'IDENTITY_COPY',
        'disk' => 'local',
        'path' => "customers/{$customer->id}/identity-copy.pdf",
        'original_name' => 'identity-copy.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
        'uploaded_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('customers.show', $customer));

    $response->assertOk()
        ->assertSee('Verify document')
        ->assertSee(route('customer-documents.verify', $document), false);
});
