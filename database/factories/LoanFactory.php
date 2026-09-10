<?php

namespace Database\Factories;

use App\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
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
            'loan_application_id' => LoanApplication::factory(),
            'loan_number' => fake()->unique()->bothify('LN-######'),
            'principal_amount' => 1000000,
            'outstanding_principal' => 1000000,
            'arrears_amount' => 0,
            'days_past_due' => 0,
            'status' => LoanStatus::Active,
            'disbursed_at' => today(),
            'maturity_date' => today()->addMonths(3),
        ];
    }
}
