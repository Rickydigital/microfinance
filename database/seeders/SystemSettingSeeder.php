<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['group' => 'institution', 'key' => 'business_date', 'value' => now()->toDateString(), 'type' => 'date', 'is_public' => false],
            ['group' => 'finance', 'key' => 'currency', 'value' => 'TZS', 'type' => 'string', 'is_public' => true],
            ['group' => 'finance', 'key' => 'currency_decimal_places', 'value' => '2', 'type' => 'integer', 'is_public' => false],
            ['group' => 'repayment', 'key' => 'allocation_order', 'value' => json_encode(['penalties', 'fees', 'interest', 'principal']), 'type' => 'json', 'is_public' => false],
            ['group' => 'arrears', 'key' => 'aging_bands', 'value' => json_encode([1, 7, 30, 60, 90]), 'type' => 'json', 'is_public' => false],
            ['group' => 'security', 'key' => 'maker_checker_enabled', 'value' => '1', 'type' => 'boolean', 'is_public' => false],
            ['group' => 'security', 'key' => 'allow_self_approval', 'value' => '0', 'type' => 'boolean', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        foreach ($this->sequences() as $sequence) {
            NumberSequence::query()->updateOrCreate(
                ['branch_id' => null, 'key' => $sequence['key']],
                array_merge(['next_number' => 1, 'reset_annually' => false], $sequence),
            );
        }
    }

    /** @return list<array{key: string, prefix: string, padding: int, reset_annually?: bool}> */
    private function sequences(): array
    {
        return [
            ['key' => 'customer', 'prefix' => 'CUS-', 'padding' => 6],
            ['key' => 'application', 'prefix' => 'APP-{YEAR}-', 'padding' => 6, 'reset_annually' => true],
            ['key' => 'loan', 'prefix' => 'LN-', 'padding' => 6],
            ['key' => 'repayment', 'prefix' => 'RP-{YEAR}-', 'padding' => 6, 'reset_annually' => true],
            ['key' => 'transaction', 'prefix' => 'TRX-{YEAR}-', 'padding' => 8, 'reset_annually' => true],
            ['key' => 'collection_case', 'prefix' => 'COL-', 'padding' => 6],
            ['key' => 'journal', 'prefix' => 'JV-{YEAR}-', 'padding' => 8, 'reset_annually' => true],
            ['key' => 'disbursement', 'prefix' => 'DSB-{YEAR}-', 'padding' => 6, 'reset_annually' => true],
            ['key' => 'collateral', 'prefix' => 'COLL-', 'padding' => 6],
        ];
    }
}
