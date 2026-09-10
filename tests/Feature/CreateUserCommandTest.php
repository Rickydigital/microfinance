<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command creates a branch-scoped employee with any configured role', function () {
    $this->seed(DatabaseSeeder::class);

    $this->artisan('user:create', [
        '--first-name' => 'Amina',
        '--last-name' => 'Mashauri',
        '--email' => 'amina@example.com',
        '--phone' => '+255700000001',
        '--staff-number' => 'STF-000001',
        '--gender' => 'Female',
        '--date-of-birth' => '1990-04-12',
        '--branch' => 'HO',
        '--department' => 'ADMIN',
        '--role' => 'System Administrator',
        '--password' => 'SecurePass123!',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'amina@example.com')->firstOrFail();

    expect($user->full_name)->toBe('Amina Mashauri')
        ->and($user->branch->code)->toBe('HO')
        ->and($user->department->code)->toBe('ADMIN')
        ->and($user->hasRole('System Administrator'))->toBeTrue();
});
