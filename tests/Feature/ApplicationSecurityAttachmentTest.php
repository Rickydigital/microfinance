<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('security creation routes only exist beneath a loan application', function (): void {
    expect(app('router')->has('loan-applications.guarantors.store'))->toBeTrue()
        ->and(app('router')->has('loan-applications.collaterals.store'))->toBeTrue()
        ->and(app('router')->has('guarantors.store'))->toBeTrue()
        ->and(app('router')->has('collaterals.store'))->toBeTrue();
});
