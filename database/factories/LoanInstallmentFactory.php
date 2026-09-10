<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanInstallment>
 */
class LoanInstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'installment_number' => 1,
            'due_date' => today()->addMonth(),
            'principal_due' => 300000,
            'interest_due' => 50000,
            'fees_due' => 0,
            'penalties_due' => 0,
            'principal_paid' => 0,
            'interest_paid' => 0,
            'fees_paid' => 0,
            'penalties_paid' => 0,
            'status' => 'Pending',
        ];
    }
}
