<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorized officer verifies each identification and address separately', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $officer = User::factory()->create();
    $officer->assignRole('System Administrator');
    $customer = Customer::factory()->create(['branch_id' => $officer->branch_id]);
    $identification = $customer->identifications()->create(['type' => 'NIDA', 'number' => '19900101-12345-00001-00', 'issuing_country' => 'TZ']);
    $address = $customer->addresses()->create(['type' => 'Residential', 'region' => 'Arusha', 'district' => 'Arusha City', 'ward' => 'Central', 'street' => 'Market Street', 'is_primary' => true]);

    expect($identification->verified_at)->toBeNull()->and($address->verified_at)->toBeNull();

    $this->actingAs($officer)->post(route('customers.identifications.verify', [$customer, $identification]))->assertSessionHasNoErrors();
    $this->actingAs($officer)->post(route('customers.addresses.verify', [$customer, $address]))->assertSessionHasNoErrors();

    expect($identification->fresh()->verified_by)->toBe($officer->id)
        ->and($identification->fresh()->verified_at)->not->toBeNull()
        ->and($address->fresh()->verified_by)->toBe($officer->id)
        ->and($address->fresh()->verified_at)->not->toBeNull();
});
