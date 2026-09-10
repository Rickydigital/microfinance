<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['code' => '1000', 'name' => 'Cash and Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_control_account' => true, 'allows_manual_posting' => false],
            ['code' => '1100', 'name' => 'Loans Receivable', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_control_account' => true, 'allows_manual_posting' => false],
            ['code' => '2000', 'name' => 'Customer Savings Liability', 'type' => 'Liability', 'normal_balance' => 'Credit', 'is_control_account' => true, 'allows_manual_posting' => false],
            ['code' => '4000', 'name' => 'Interest Income', 'type' => 'Income', 'normal_balance' => 'Credit', 'is_control_account' => false, 'allows_manual_posting' => false],
            ['code' => '4100', 'name' => 'Fee Income', 'type' => 'Income', 'normal_balance' => 'Credit', 'is_control_account' => false, 'allows_manual_posting' => false],
            ['code' => '4200', 'name' => 'Penalty Income', 'type' => 'Income', 'normal_balance' => 'Credit', 'is_control_account' => false, 'allows_manual_posting' => false],
            ['code' => '5000', 'name' => 'Operating Expenses', 'type' => 'Expense', 'normal_balance' => 'Debit', 'is_control_account' => false, 'allows_manual_posting' => true],
        ] as $account) {
            ChartOfAccount::query()->updateOrCreate(['code' => $account['code']], $account + ['is_active' => true]);
        }
    }
}
