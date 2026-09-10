<?php

namespace Database\Seeders;

use App\Models\AccountingPeriod;
use Illuminate\Database\Seeder;

class AccountingPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AccountingPeriod::query()->updateOrCreate(
            ['starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString()],
            ['name' => now()->format('Y').' Financial Year', 'status' => 'Open'],
        );
    }
}
