<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InstitutionSeeder::class,
            BranchSeeder::class,
            RolePermissionSeeder::class,
            SystemSettingSeeder::class,
            CustomerTypeSeeder::class,
            IdentificationTypeSeeder::class,
            DocumentTypeSeeder::class,
            AdministrativeHierarchySeeder::class,
            LoanProductSeeder::class,
            MyCashHistoricalLoansSeeder::class,
            ApprovalRuleSeeder::class,
            ChartOfAccountSeeder::class,
            AccountingPeriodSeeder::class,
        ]);
    }
}
