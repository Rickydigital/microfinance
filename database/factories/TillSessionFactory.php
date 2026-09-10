<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\TillSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TillSession>
 */
class TillSessionFactory extends Factory
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
            'user_id' => User::factory(),
            'opening_balance' => 100000,
            'expected_balance' => 100000,
            'actual_balance' => null,
            'difference_amount' => null,
            'difference_reason' => null,
            'status' => 'Open',
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }
}
