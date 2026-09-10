<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['code' => 'PROFILE_PHOTO', 'name' => 'Customer photograph', 'customer_type_code' => 'Individual', 'is_required_for_kyc' => true, 'sort_order' => 10],
            ['code' => 'IDENTITY_COPY', 'name' => 'Identification copy', 'customer_type_code' => null, 'is_required_for_kyc' => true, 'sort_order' => 20],
            ['code' => 'PROOF_OF_ADDRESS', 'name' => 'Proof of address', 'customer_type_code' => null, 'is_required_for_kyc' => true, 'sort_order' => 30],
            ['code' => 'BUSINESS_CERTIFICATE', 'name' => 'Business registration certificate', 'customer_type_code' => 'Business', 'is_required_for_kyc' => true, 'sort_order' => 40],
            ['code' => 'GROUP_CONSTITUTION', 'name' => 'Group constitution', 'customer_type_code' => 'Group', 'is_required_for_kyc' => true, 'sort_order' => 50],
            ['code' => 'BANK_STATEMENT', 'name' => 'Bank statement', 'customer_type_code' => null, 'is_required_for_kyc' => false, 'sort_order' => 60],
            ['code' => 'OTHER', 'name' => 'Other supporting document', 'customer_type_code' => null, 'is_required_for_kyc' => false, 'sort_order' => 100],
        ] as $type) {
            DocumentType::query()->updateOrCreate(['code' => $type['code']], $type + ['is_active' => true]);
        }
    }
}
