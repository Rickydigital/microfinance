<?php

namespace Database\Factories;

use App\LoanApplicationStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProductVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanApplication>
 */
class LoanApplicationFactory extends Factory
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
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'loan_product_version_id' => LoanProductVersion::factory(),
            'application_number' => fake()->unique()->bothify('APP-######'),
            'source' => 'branch',
            'requested_amount' => fake()->numberBetween(100000, 10000000),
            'requested_term' => 12,
            'repayment_frequency' => 'Monthly',
            'purpose' => fake()->sentence(),
            'declared_monthly_income' => 1000000,
            'declared_monthly_expenses' => 400000,
            'existing_debt_payment' => 0,
            'approved_amount' => null,
            'status' => LoanApplicationStatus::Submitted,
            'submitted_at' => now(),
            'decided_at' => null,
        ];
    }
}
