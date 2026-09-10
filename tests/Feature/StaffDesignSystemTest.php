<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff screens load the shared design system without replacing staff navigation', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('System Administrator');

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('app-assets/css/staff-design-system.css', false)
        ->assertSee('class="sidebar"', false)
        ->assertSee('class="topbar"', false);
});

test('staff authentication screens load the shared design system', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('app-assets/css/staff-design-system.css', false)
        ->assertSee('class="auth-shell"', false);
});

test('select2 dropdowns are attached outside cards so suggestions remain visible', function (): void {
    $javascript = file_get_contents(public_path('app-assets/js/microfinance.js'));
    $stylesheet = file_get_contents(public_path('app-assets/css/staff-design-system.css'));

    expect($javascript)
        ->toContain('var dropdownParent = modal.length ? modal : window.jQuery(document.body);')
        ->not->toContain("dropdownParent: select.closest('.card, .modal, form').first()")
        ->and($stylesheet)
        ->toContain('z-index: 1090;');
});
