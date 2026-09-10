<?php

namespace Database\Factories;

use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanProduct>
 */
class LoanProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('LP-####'),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
