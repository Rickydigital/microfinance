<?php

use App\Models\Customer;
use App\Models\District;
use App\Models\IdentificationType;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\Region;
use App\Models\Street;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\AdministrativeHierarchySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer profile can register guarantor collateral and hierarchical address', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SystemSettingSeeder::class);
    IdentificationType::factory()->create(['code' => 'NIDA']);
    $officer = User::factory()->create();
    $officer->assignRole('System Administrator');
    $customer = Customer::factory()->create(['branch_id' => $officer->branch_id]);
    $region = Region::factory()->create();
    $district = District::factory()->create(['region_id' => $region->id]);
    $ward = Ward::factory()->create(['district_id' => $district->id]);
    $street = Street::factory()->create(['ward_id' => $ward->id]);
    $application = LoanApplication::factory()->create(['branch_id' => $officer->branch_id, 'customer_id' => $customer->id, 'created_by' => $officer->id, 'loan_product_version_id' => LoanProductVersion::factory()]);

    $this->actingAs($officer)->post(route('customers.addresses.store', $customer), ['type' => 'Residential', 'region_id' => $region->id, 'district_id' => $district->id, 'ward_id' => $ward->id, 'street_id' => $street->id, 'is_primary' => 1])->assertSessionHasNoErrors();
    $this->actingAs($officer)->post(route('loan-applications.guarantors.store', $application), ['first_name' => 'Amina', 'last_name' => 'Juma', 'phone' => '255700000001', 'identification_type' => 'NIDA', 'identification_number' => 'NIDA-1', 'monthly_income' => 500000, 'guaranteed_amount' => 100000, 'consent_received' => 1])->assertSessionHasNoErrors();
    $this->actingAs($officer)->post(route('loan-applications.collaterals.store', $application), ['type' => 'Land', 'description' => 'Residential plot', 'ownership_reference' => 'TITLE-1', 'market_value' => 5000000, 'forced_sale_value' => 3500000, 'pledged_value' => 3000000, 'valuer' => 'Independent Valuer', 'valuation_date' => today()->toDateString()])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('customer_addresses', ['customer_id' => $customer->id, 'region_id' => $region->id, 'district' => $district->name]);
    $this->assertDatabaseHas('guarantors', ['principal_customer_id' => $customer->id, 'first_name' => 'Amina']);
    $this->assertDatabaseHas('collaterals', ['customer_id' => $customer->id, 'description' => 'Residential plot']);
    $this->assertDatabaseHas('loan_guarantors', ['loan_application_id' => $application->id]);
    $this->assertDatabaseHas('loan_collaterals', ['loan_application_id' => $application->id]);
    $this->actingAs($officer)->get(route('locations.districts', ['region_id' => $region->id]))->assertOk()->assertJsonFragment(['name' => $district->name]);
    $this->actingAs($officer)->get(route('locations.streets', ['ward_id' => $ward->id]))->assertOk()->assertJsonFragment(['name' => $street->name]);
});

test('copied o3plus administrative hierarchy imports regions districts and wards', function (): void {
    $this->seed(AdministrativeHierarchySeeder::class);

    $this->assertDatabaseHas('regions', ['code' => '100001', 'name' => 'Arusha']);
    $this->assertDatabaseHas('districts', ['code' => '100030', 'name' => 'Arumeru']);
    $this->assertDatabaseHas('wards', ['code' => '100091', 'name' => 'Ilboru Ward']);
    $this->assertDatabaseHas('streets', ['code' => '100848', 'name' => 'Meriti Kitongoji']);
});
