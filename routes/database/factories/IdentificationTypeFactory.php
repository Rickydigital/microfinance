<?php

namespace Database\Factories;

use App\Models\IdentificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentificationType>
 */
class IdentificationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('ID-????'),
            'name' => fake()->unique()->words(2, true),
            'customer_type_code' => null,
            'number_pattern' => null,
            'pattern_hint' => null,
            'requires_expiry' => false,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
