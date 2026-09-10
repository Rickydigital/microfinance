<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['code' => 'Individual', 'name' => 'Individual', 'description' => 'A natural person', 'is_legal_entity' => false, 'sort_order' => 10],
            ['code' => 'Business', 'name' => 'Business', 'description' => 'A registered or unregistered business', 'is_legal_entity' => true, 'sort_order' => 20],
            ['code' => 'Group', 'name' => 'Group', 'description' => 'A borrowing or savings group', 'is_legal_entity' => true, 'sort_order' => 30],
        ] as $type) {
            CustomerType::query()->updateOrCreate(['code' => $type['code']], $type + ['is_active' => true]);
        }
    }
}
