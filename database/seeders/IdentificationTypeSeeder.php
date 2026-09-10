<?php

namespace Database\Seeders;

use App\Models\IdentificationType;
use Illuminate\Database\Seeder;

class IdentificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['code' => 'NIDA', 'name' => 'National ID (NIDA)', 'customer_type_code' => 'Individual', 'pattern_hint' => '20 digits', 'requires_expiry' => false, 'sort_order' => 10],
            ['code' => 'PASSPORT', 'name' => 'Passport', 'customer_type_code' => 'Individual', 'pattern_hint' => 'Passport number', 'requires_expiry' => true, 'sort_order' => 20],
            ['code' => 'DRIVING_LICENCE', 'name' => 'Driving licence', 'customer_type_code' => 'Individual', 'pattern_hint' => 'Licence number', 'requires_expiry' => true, 'sort_order' => 30],
            ['code' => 'BRELA', 'name' => 'Business registration (BRELA)', 'customer_type_code' => 'Business', 'pattern_hint' => 'Registration number', 'requires_expiry' => false, 'sort_order' => 40],
            ['code' => 'TIN', 'name' => 'Taxpayer identification (TIN)', 'customer_type_code' => 'Business', 'pattern_hint' => 'TIN number', 'requires_expiry' => false, 'sort_order' => 50],
            ['code' => 'GROUP_CERTIFICATE', 'name' => 'Group registration certificate', 'customer_type_code' => 'Group', 'pattern_hint' => 'Certificate number', 'requires_expiry' => false, 'sort_order' => 60],
        ] as $type) {
            IdentificationType::query()->updateOrCreate(['code' => $type['code']], $type + ['number_pattern' => null, 'is_active' => true]);
        }
    }
}
