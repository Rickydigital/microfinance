<?php

namespace Database\Seeders;

use App\Models\ApprovalRule;
use App\Models\LoanProduct;
use App\Models\LoanProductVersion;
use App\Models\ProductFee;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = LoanProduct::query()->updateOrCreate(
            ['code' => 'BUSINESS'],
            ['name' => 'Business Loan', 'description' => 'Working-capital and business-expansion finance.', 'is_active' => true],
        );

        $version = LoanProductVersion::query()->updateOrCreate(
            ['loan_product_id' => $product->id, 'version' => 1],
            [
                'minimum_amount' => 100000,
                'maximum_amount' => 20000000,
                'minimum_term' => 1,
                'maximum_term' => 24,
                'term_unit' => 'Months',
                'interest_method' => 'Reducing Balance',
                'interest_rate' => 0.24,
                'rate_basis' => 'Annual',
                'repayment_frequency' => 'Monthly',
                'required_guarantors' => 1,
                'requires_collateral' => false,
                'maximum_installment_ratio' => 0.50,
                'effective_from' => today(),
            ],
        );

        ProductFee::query()->updateOrCreate(
            ['loan_product_version_id' => $version->id, 'name' => 'Processing Fee'],
            ['calculation_method' => 'Percentage', 'value' => 0.02, 'collection_point' => 'Disbursement', 'is_financed' => false],
        );

        foreach ([
            [0, 1000000, 'Branch Manager', 1],
            [1000000.01, 5000000, 'Branch Manager', 1],
            [1000000.01, 5000000, 'Credit Manager', 2],
            [5000000.01, 20000000, 'Credit Manager', 1],
            [5000000.01, 20000000, 'CEO', 2],
        ] as [$minimum, $maximum, $role, $sequence]) {
            ApprovalRule::query()->updateOrCreate(
                ['loan_product_id' => $product->id, 'branch_id' => null, 'role_name' => $role, 'sequence' => $sequence],
                ['minimum_amount' => $minimum, 'maximum_amount' => $maximum, 'is_active' => true],
            );
        }
    }
}
