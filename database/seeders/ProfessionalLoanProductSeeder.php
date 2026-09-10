<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use Illuminate\Database\Seeder;

class ProfessionalLoanProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = LoanProduct::query()->updateOrCreate(
            ['code' => 'STANDARD-MICRO'],
            [
                'name' => 'Standard Micro Loan',
                'description' => 'General-purpose reducing-balance microfinance loan.',
                'is_active' => true,
            ],
        );

        $version = $product->versions()->updateOrCreate(
            ['version' => 1],
            [
                'minimum_amount' => 100000,
                'maximum_amount' => 5000000,
                'minimum_term' => 1,
                'maximum_term' => 24,
                'term_unit' => 'Months',
                'interest_method' => 'Reducing Balance',
                'interest_rate' => 0.24,
                'rate_basis' => 'Annual',
                'repayment_frequency' => 'Monthly',
                'principal_grace_period' => 0,
                'interest_grace_period' => 0,
                'required_guarantors' => 1,
                'requires_collateral' => false,
                'maximum_installment_ratio' => 0.50,
                'effective_from' => today(),
                'effective_to' => null,
            ],
        );

        $version->fees()->updateOrCreate(
            ['name' => 'Processing Fee'],
            [
                'calculation_method' => 'Fixed',
                'value' => 10000,
                'collection_point' => 'Disbursement',
                'is_financed' => false,
            ],
        );

        $version->penaltyRules()->updateOrCreate(
            ['name' => 'Late Payment Penalty'],
            [
                'calculation_method' => 'Percentage',
                'calculation_basis' => 'Overdue Principal',
                'value' => 0.01,
                'grace_days' => 3,
                'frequency' => 'Daily',
                'maximum_amount' => 50000,
                'is_active' => true,
            ],
        );
    }
}
