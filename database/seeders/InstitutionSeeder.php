<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Institution::query()->updateOrCreate(
            ['code' => config('microfinance.defaults.institution.code')],
            config('microfinance.defaults.institution'),
        );
    }
}
