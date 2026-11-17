<?php

namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerType>
 */
class CustomerTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('TYPE-????'),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'is_legal_entity' => false,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
