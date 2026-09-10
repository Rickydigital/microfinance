<?php

namespace Database\Seeders;

use App\Models\ApprovalRule;
use Illuminate\Database\Seeder;

class ApprovalRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            ['minimum_amount' => 0, 'maximum_amount' => 5000000, 'role_name' => 'Branch Manager', 'sequence' => 1],
            ['minimum_amount' => 5000000.01, 'maximum_amount' => 20000000, 'role_name' => 'Credit Manager', 'sequence' => 1],
            ['minimum_amount' => 20000000.01, 'maximum_amount' => null, 'role_name' => 'Credit Manager', 'sequence' => 1],
            ['minimum_amount' => 20000000.01, 'maximum_amount' => null, 'role_name' => 'Credit Committee Member', 'sequence' => 2],
        ];

        foreach ($rules as $rule) {
            ApprovalRule::query()->updateOrCreate(
                ['loan_product_id' => null, 'branch_id' => null, 'role_name' => $rule['role_name'], 'sequence' => $rule['sequence']],
                [...$rule, 'risk_grade' => null, 'is_active' => true],
            );
        }
    }
}
