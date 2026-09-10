<?php

namespace Database\Factories;

use App\CustomerStatus;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'customer_number' => fake()->unique()->bothify('MFI-CUS-######'),
            'type' => 'Individual',
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'identification_type' => 'National ID',
            'identification_number' => fake()->unique()->numerify('####################'),
            'status' => CustomerStatus::Active,
            'verified_at' => now(),
        ];
    }
}
