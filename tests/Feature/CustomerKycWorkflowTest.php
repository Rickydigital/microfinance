<?php

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\District;
use App\Models\Region;
use App\Models\Street;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\IdentificationTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('customer activation requires complete verified kyc records', function () {
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);
    $this->seed(IdentificationTypeSeeder::class);
    $officer = User::factory()->create();
    $officer->assignRole('Customer KYC Officer');
    $customer = Customer::factory()->for($officer->branch)->create(['status' => CustomerStatus::PendingVerification, 'verified_at' => null]);
    $region = Region::factory()->create();
    $district = District::factory()->create(['region_id' => $region->id]);
    $ward = Ward::factory()->create(['district_id' => $district->id]);
    $street = Street::factory()->create(['ward_id' => $ward->id]);

    $this->actingAs($officer)->post(route('customers.addresses.store', $customer), ['type' => 'Residential', 'region_id' => $region->id, 'district_id' => $district->id, 'ward_id' => $ward->id, 'street_id' => $street->id, 'is_primary' => 1])->assertSessionHasNoErrors();
    $address = $customer->addresses()->firstOrFail();
    $this->actingAs($officer)->post(route('customers.addresses.verify', [$customer, $address]))->assertSessionHasNoErrors();
    $this->actingAs($officer)->post(route('customers.identifications.store', $customer), ['type' => 'NIDA', 'number' => '19900101-12345-00001-11', 'issuing_country' => 'TZ'])->assertSessionHasNoErrors();
    $identification = $customer->identifications()->firstOrFail();
    $this->actingAs($officer)->post(route('customers.identifications.verify', [$customer, $identification]))->assertSessionHasNoErrors();
    $this->actingAs($officer)->post(route('customers.economic-profiles.store', $customer), ['occupation' => 'Trader', 'monthly_income' => 1000000, 'monthly_expenses' => 400000, 'existing_debt_payment' => 0, 'income_source' => 'Retail business'])->assertSessionHasNoErrors();
    $this->actingAs($officer)->post(route('customers.verify', $customer), ['kyc_risk_rating' => 'Low'])->assertSessionHasNoErrors();

    expect($customer->refresh()->status)->toBe(CustomerStatus::Active)
        ->and($customer->verified_at)->not->toBeNull();
});
