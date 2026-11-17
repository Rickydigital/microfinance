<?php

namespace Database\Factories;

use App\Models\ApprovalRule;
use App\Models\Branch;
use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRule>
 */
class ApprovalRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_product_id' => LoanProduct::factory(),
            'branch_id' => Branch::factory(),
            'minimum_amount' => 0,
            'maximum_amount' => fake()->numberBetween(1000000, 10000000),
            'risk_grade' => null,
            'role_name' => 'Credit Manager',
            'sequence' => 1,
            'is_active' => true,
        ];
    }
}
