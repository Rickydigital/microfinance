<?php

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('presents core administration actions in consistent modals', function (): void {
    $this->seed(RolePermissionSeeder::class);
    Branch::factory()->create();
    $administrator = User::factory()->create();
    $administrator->assignRole('System Administrator');

    $this->actingAs($administrator)->get(route('users.index'))
        ->assertOk()->assertSee('admin-hero')->assertSee('id="create-employee"', false);
    $this->actingAs($administrator)->get(route('roles.index'))
        ->assertOk()->assertSee('admin-hero')->assertSee('id="create-role"', false);
    $this->actingAs($administrator)->get(route('approval-rules.index'))
        ->assertOk()->assertSee('admin-hero')->assertSee('id="create-approval-rule"', false);
    $this->actingAs($administrator)->get(route('branches.index'))
        ->assertOk()->assertSee('admin-hero')->assertSee('id="create-branch"', false);
    $this->actingAs($administrator)->get(route('settings.index'))
        ->assertOk()->assertSee('admin-hero')->assertSee('id="edit-settings"', false);
});
