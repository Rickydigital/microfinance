<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrator can manage customer and identification types', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('System Administrator');

    $this->actingAs($administrator)->post(route('reference-data.customer-types.store'), [
        'code' => 'SACCO',
        'name' => 'SACCO',
        'is_legal_entity' => 1,
    ])->assertRedirect();

    $this->actingAs($administrator)->post(route('reference-data.identification-types.store'), [
        'code' => 'SACCO_CERT',
        'name' => 'SACCO certificate',
        'customer_type_code' => 'SACCO',
        'requires_expiry' => 0,
    ])->assertRedirect();

    $this->assertDatabaseHas('customer_types', ['code' => 'SACCO', 'is_active' => true]);
    $this->assertDatabaseHas('identification_types', ['code' => 'SACCO_CERT', 'customer_type_code' => 'SACCO']);

    $this->actingAs($administrator)->post(route('reference-data.document-types.store'), [
        'code' => 'SACCO_BYLAWS',
        'name' => 'SACCO bylaws',
        'customer_type_code' => 'SACCO',
        'is_required_for_kyc' => 1,
    ])->assertRedirect();
    $this->assertDatabaseHas('document_types', ['code' => 'SACCO_BYLAWS', 'is_required_for_kyc' => true]);
});
