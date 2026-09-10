<?php

use App\Models\User;
use App\Services\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('another employee authorizes an explained till difference', function () {
    $cashier = User::factory()->create();
    $manager = User::factory()->for($cashier->branch)->create();
    $service = app(TillSessionService::class);
    $session = $service->open($cashier, '100000.00');
    $session = $service->close($session, '99000.00', 'Cash count shortage confirmed.');

    expect($session->status)->toBe('Pending Difference Approval');

    $session = $service->authorizeDifference($session, $manager);

    expect($session->status)->toBe('Closed')
        ->and($session->difference_authorized_by)->toBe($manager->id)
        ->and($session->difference_authorized_at)->not->toBeNull();
});
