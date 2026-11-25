<?php

it('keeps the customer modal location cascade valid and connected to its endpoints', function (): void {
    $view = file_get_contents(resource_path('views/customers/index.blade.php'));

    expect($view)
        ->not->toBeFalse()
        ->not->toMatch('/\?\s+\?/')
        ->not->toMatch('/\?\s+\./')
        ->toContain("district: @json(route('locations.districts'))")
        ->toContain("ward: @json(route('locations.wards'))")
        ->toContain("street: @json(route('locations.streets'))")
        ->toContain("const failedModalTarget = @json(old('_modal_target'))")
        ->toContain(".on('change.customerLocation', '.customer-location select[data-location]'");
});
