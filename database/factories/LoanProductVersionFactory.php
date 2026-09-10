<?php

namespace Database\Factories;

use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanProductVersion>
 */
class LoanProductVersionFactory extends Factory
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
            'version' => 1,
            'minimum_amount' => 100000,
            'maximum_amount' => 10000000,
            'minimum_term' => 1,
            'maximum_term' => 24,
            'term_unit' => 'Months',
            'interest_method' => 'Reducing Balance',
            'interest_rate' => 0.24,
            'rate_basis' => 'Annual',
            'repayment_frequency' => 'Monthly',
            'principal_grace_period' => 0,
            'interest_grace_period' => 0,
            'required_guarantors' => 0,
            'requires_collateral' => false,
            'maximum_installment_ratio' => 0.50,
            'effective_from' => today(),
            'effective_to' => null,
        ];
    }
}
