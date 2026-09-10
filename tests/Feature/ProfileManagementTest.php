<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can open and update own profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('My profile');
    $this->actingAs($user)->put(route('profile.update'), [
        'first_name' => 'Rehema',
        'middle_name' => null,
        'last_name' => 'Mushi',
        'phone' => '+255700123456',
        'email' => 'rehema@example.test',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => 'Rehema', 'email' => 'rehema@example.test']);
});
