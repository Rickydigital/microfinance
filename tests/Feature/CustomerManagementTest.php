<?php

use App\Models\Customer;
use App\Models\District;
use App\Models\Region;
use App\Models\Street;
use App\Models\User;
use App\Models\Ward;
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

test('editing updates the current economic profile kyc risk and primary address', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(CustomerTypeSeeder::class);
    $this->seed(IdentificationTypeSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Customer KYC Officer');
    $customer = Customer::factory()->create(['branch_id' => $user->branch_id, 'type' => 'Individual']);
    $economicProfile = $customer->economicProfiles()->create([
        'occupation' => 'Trader',
        'income_source' => 'Custom wholesale business',
        'monthly_income' => 1000000,
        'monthly_expenses' => 300000,
        'existing_debt_payment' => 0,
        'effective_from' => today()->subMonth(),
    ]);
    $oldRegion = Region::factory()->create();
    $oldDistrict = District::factory()->create(['region_id' => $oldRegion->id]);
    $oldWard = Ward::factory()->create(['district_id' => $oldDistrict->id]);
    $oldStreet = Street::factory()->create(['ward_id' => $oldWard->id]);
    $primaryAddress = $customer->addresses()->create([
        'type' => 'Residential',
        'region_id' => $oldRegion->id,
        'district_id' => $oldDistrict->id,
        'ward_id' => $oldWard->id,
        'street_id' => $oldStreet->id,
        'region' => $oldRegion->name,
        'district' => $oldDistrict->name,
        'ward' => $oldWard->name,
        'street' => $oldStreet->name,
        'is_primary' => true,
        'verified_by' => $user->id,
        'verified_at' => now(),
    ]);
    $region = Region::factory()->create();
    $district = District::factory()->create(['region_id' => $region->id]);
    $ward = Ward::factory()->create(['district_id' => $district->id]);
    $street = Street::factory()->create(['ward_id' => $ward->id]);

    $this->actingAs($user)->put(route('customers.update', $customer), [
        'type' => 'Individual',
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'phone' => $customer->phone,
        'kyc_risk_rating' => 'High',
        'address' => [
            'region_id' => $region->id,
            'district_id' => $district->id,
            'ward_id' => $ward->id,
            'street_id' => $street->id,
            'description' => 'Near the market',
        ],
        'economic' => [
            'occupation' => 'Shop owner',
            'income_source' => 'Custom wholesale business',
            'monthly_income' => 1750000,
            'monthly_expenses' => 450000,
            'existing_debt_payment' => 50000,
        ],
    ])->assertRedirect(route('customers.show', $customer))->assertSessionHasNoErrors();

    expect($customer->fresh()->kyc_risk_rating)->toBe('High')
        ->and($customer->economicProfiles()->whereNull('effective_to')->count())->toBe(1)
        ->and($economicProfile->fresh()->occupation)->toBe('Shop owner')
        ->and((float) $economicProfile->fresh()->monthly_income)->toBe(1750000.0)
        ->and($customer->addresses()->where('is_primary', true)->count())->toBe(1)
        ->and($primaryAddress->fresh()->region_id)->toBe($region->id)
        ->and($primaryAddress->fresh()->description)->toBe('Near the market')
        ->and($primaryAddress->fresh()->verified_at)->toBeNull();
});

test('editing synchronizes next of kin without creating duplicate records', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(CustomerTypeSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Customer KYC Officer');
    $customer = Customer::factory()->create(['branch_id' => $user->branch_id, 'type' => 'Individual']);
    $firstKin = $customer->relationships()->create(['type' => 'Next of Kin', 'full_name' => 'Amina Musa', 'phone' => '0712000000']);
    $duplicateKin = $customer->relationships()->create(['type' => 'Next of Kin', 'full_name' => 'Amina Musa', 'phone' => '0712000000']);

    $kin = collect(range(1, 6))->map(fn (): array => [
        'id' => $firstKin->id,
        'full_name' => 'Amina Musa',
        'phone' => '0712000000',
        'address' => 'Dar es Salaam',
    ])->all();
    $kin[1]['id'] = $duplicateKin->id;

    $this->actingAs($user)->put(route('customers.update', $customer), [
        'type' => 'Individual',
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'phone' => $customer->phone,
        'kin' => $kin,
    ])->assertSessionHasNoErrors();

    expect($customer->relationships()->where('type', 'Next of Kin')->count())->toBe(1)
        ->and($firstKin->fresh()->address)->toBe('Dar es Salaam');
});

test('an invalid customer update preserves the edit modal target', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(CustomerTypeSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Customer KYC Officer');
    $customer = Customer::factory()->create(['branch_id' => $user->branch_id, 'type' => 'Individual']);

    $this->actingAs($user)
        ->from(route('customers.index'))
        ->put(route('customers.update', $customer), [
            '_modal_target' => 'edit-'.$customer->id,
            'type' => 'Individual',
            'first_name' => $customer->first_name,
            'last_name' => '',
            'phone' => $customer->phone,
        ])
        ->assertRedirect(route('customers.index'))
        ->assertSessionHasErrors('last_name')
        ->assertSessionHasInput('_modal_target', 'edit-'.$customer->id);
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
