<?php

use App\Models\ApprovalRule;
use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('authorized administrator can create an approval rule', function (): void {
    $user = User::factory()->create();
    $user->assignRole('System Administrator');
    $product = LoanProduct::factory()->create(['is_active' => true]);
    $branch = Branch::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->post(route('approval-rules.store'), [
        'loan_product_id' => $product->id,
        'branch_id' => $branch->id,
        'minimum_amount' => 100000,
        'maximum_amount' => 1000000,
        'role_name' => 'Credit Manager',
        'sequence' => 1,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('approval-rules.index'));
    $this->assertDatabaseHas('approval_rules', ['loan_product_id' => $product->id, 'role_name' => 'Credit Manager', 'sequence' => 1]);
});

test('overlapping active approval bands at the same level are rejected', function (): void {
    $user = User::factory()->create();
    $user->assignRole('System Administrator');
    $product = LoanProduct::factory()->create(['is_active' => true]);
    $branch = Branch::factory()->create(['is_active' => true]);
    ApprovalRule::factory()->create(['loan_product_id' => $product->id, 'branch_id' => $branch->id, 'minimum_amount' => 0, 'maximum_amount' => 500000]);

    $response = $this->actingAs($user)->from(route('approval-rules.create'))->post(route('approval-rules.store'), [
        'loan_product_id' => $product->id,
        'branch_id' => $branch->id,
        'minimum_amount' => 400000,
        'maximum_amount' => 900000,
        'role_name' => 'Credit Manager',
        'sequence' => 1,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('approval-rules.create'))->assertSessionHasErrors('minimum_amount');
    expect(ApprovalRule::query()->count())->toBe(1);
});

test('user without approval visibility cannot open approval rules', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('approval-rules.index'))->assertForbidden();
});
