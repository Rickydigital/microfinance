<?php

use App\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\LoanApplicationWorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a draft loan application can be submitted', function () {
    $application = LoanApplication::factory()->create([
        'status' => LoanApplicationStatus::Draft,
    ]);

    $submitted = app(LoanApplicationWorkflowService::class)->submit($application);

    expect($submitted->status)->toBe(LoanApplicationStatus::Submitted)
        ->and($submitted->submitted_at)->not->toBeNull();
});

test('an authenticated employee can view the application register', function () {
    $user = User::factory()->create();
    $this->seed(RolePermissionSeeder::class);
    $user->assignRole('Loan Officer');

    $this->actingAs($user)
        ->get(route('loan-applications.index'))
        ->assertOk()
        ->assertViewIs('loan-applications.index');
});
