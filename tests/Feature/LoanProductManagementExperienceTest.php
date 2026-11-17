<?php

use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->staff = User::factory()->create();
    $this->staff->givePermissionTo(['loan-product.view', 'loan-product.create', 'loan-product.update', 'loan-product.activate', 'loan-product.version.create']);
});

test('staff creates a ready to use product and first version in one step', function (): void {
    $response = $this->actingAs($this->staff)->post(route('loan-products.store'), [
        'name' => 'Business Growth Loan', 'description' => 'Working capital for established businesses.',
        'minimum_amount' => 50000, 'maximum_amount' => 5000000, 'minimum_term' => 1, 'maximum_term' => 12,
        'term_unit' => 'Months', 'interest_method' => 'Reducing Balance', 'interest_rate_percent' => 18,
        'rate_basis' => 'Annual', 'repayment_frequency' => 'Monthly', 'principal_grace_period' => 0,
        'interest_grace_period' => 0, 'required_guarantors' => 1, 'requires_collateral' => 1,
        'maximum_installment_ratio_percent' => 50, 'assurance_percent' => 3, 'processing_fee' => 15000,
        'effective_from' => today()->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('loan-products.index'))->assertSessionHasNoErrors();
    $product = LoanProduct::query()->where('name', 'Business Growth Loan')->firstOrFail();
    $version = $product->latestVersion;
    expect($product->versions)->toHaveCount(1)
        ->and($version->interest_rate)->toBe('0.180000')
        ->and($version->requires_collateral)->toBeTrue()
        ->and((float) $version->fees()->where('name', 'Processing fee')->value('value'))->toBe(15000.0)
        ->and($version->fees()->where('name', 'Processing fee')->value('calculation_method'))->toBe('Fixed')
        ->and($version->fees()->where('name', 'Processing fee')->value('collection_point'))->toBe('Installment')
        ->and((bool) $version->fees()->where('name', 'Processing fee')->value('is_financed'))->toBeTrue();
});

test('product register presents searchable cards and management modals', function (): void {
    $version = LoanProductVersion::factory()->create();
    $product = $version->product;
    $product->update(['name' => 'Agricultural Growth']);

    $this->actingAs($this->staff)->get(route('loan-products.index', ['search' => 'Agricultural', 'status' => 'active']))
        ->assertOk()->assertSee('Agricultural Growth')->assertSee('TZS 100,000')
        ->assertSee('id="view-product-'.$product->id.'"', false)
        ->assertSee('id="edit-product-'.$product->id.'"', false)
        ->assertSee('Publish new version')
        ->assertSee('Interest rate period')
        ->assertSee('Maximum instalment as % of income');
});

test('version history uses focused management modals', function (): void {
    $version = LoanProductVersion::factory()->create();

    $this->staff->givePermissionTo(['loan-product.fee.manage', 'loan-product.accounting.manage']);

    $this->actingAs($this->staff)->get(route('loan-products.show', $version->product))
        ->assertOk()
        ->assertSee('Current configuration')
        ->assertSee('Immutable audit history')
        ->assertSee('id="view-version-'.$version->id.'"', false)
        ->assertSee('id="fee-version-'.$version->id.'"', false)
        ->assertSee('id="penalty-version-'.$version->id.'"', false)
        ->assertSee('id="accounts-version-'.$version->id.'"', false)
        ->assertSee('name="accounts[loan_receivable]"', false);
});
