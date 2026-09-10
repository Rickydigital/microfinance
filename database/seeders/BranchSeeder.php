<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institution = Institution::query()->where('code', config('microfinance.defaults.institution.code'))->firstOrFail();
        $branch = Branch::query()->updateOrCreate(
            ['code' => config('microfinance.defaults.branch.code')],
            array_merge(config('microfinance.defaults.branch'), ['institution_id' => $institution->id, 'is_active' => true]),
        );

        foreach ($this->departments() as $code => $name) {
            Department::query()->updateOrCreate(['code' => $code], ['branch_id' => $branch->id, 'name' => $name, 'is_active' => true]);
        }
    }

    /** @return array<string, string> */
    private function departments(): array
    {
        return [
            'ADMIN' => 'Administration',
            'CUSTOMER' => 'Customer & KYC',
            'CREDIT' => 'Credit',
            'OPERATIONS' => 'Operations',
            'FINANCE' => 'Finance',
            'RECOVERY' => 'Collections & Recovery',
            'AUDIT' => 'Internal Audit',
        ];
    }
}
