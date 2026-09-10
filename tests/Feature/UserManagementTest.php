<?php

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every seeded role only references permissions in the managed catalogue', function () {
    $permissions = config('access-control.permissions');
    $assignedPermissions = collect(config('access-control.roles'))->flatten()->unique();

    expect($permissions)->toHaveCount(count(array_unique($permissions)))
        ->and($assignedPermissions->diff($permissions)->all())->toBeEmpty()
        ->and(config('access-control.roles.System Administrator'))->toEqualCanonicalizing($permissions);
});

test('an administrator can create an employee with a role', function () {
    $this->seed(RolePermissionSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('System Administrator');
    $branch = Branch::factory()->create();

    $response = $this->actingAs($administrator)->post(route('users.store'), [
        'branch_id' => $branch->id,
        'staff_number' => 'STF-10001',
        'first_name' => 'Neema',
        'last_name' => 'Mollel',
        'gender' => 'Female',
        'email' => 'neema@example.test',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'role' => 'Loan Officer',
        'is_active' => true,
    ]);

    $employee = User::query()->where('staff_number', 'STF-10001')->firstOrFail();
    $response->assertRedirect(route('users.show', $employee));
    expect($employee->hasRole('Loan Officer'))->toBeTrue();
});
