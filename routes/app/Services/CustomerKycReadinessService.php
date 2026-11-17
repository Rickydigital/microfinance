<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DocumentType;

class CustomerKycReadinessService
{
    /** @return array{ready: bool, completed: int, total: int, checks: array<int, array{label: string, passed: bool, detail: string}>} */
    public function evaluate(Customer $customer): array
    {
        $requiredDocumentTypes = DocumentType::query()->where('is_active', true)->where('is_required_for_kyc', true)
            ->where(fn ($query) => $query->whereNull('customer_type_code')->orWhere('customer_type_code', $customer->type))
            ->orderBy('sort_order')->get();
        $checks = [
            ['label' => 'Verified primary address', 'passed' => $customer->addresses()->where('is_primary', true)->whereNotNull('verified_at')->exists(), 'detail' => 'A primary address must be independently verified.'],
            ['label' => 'Verified valid identification', 'passed' => $customer->identifications()->whereNotNull('verified_at')->where(fn ($query) => $query->whereNull('expires_at')->orWhereDate('expires_at', '>', today()))->exists(), 'detail' => 'At least one verified and unexpired identification is required.'],
            ['label' => 'Current economic profile', 'passed' => $customer->economicProfiles()->whereNull('effective_to')->exists(), 'detail' => 'Current income, expenses, debt, and income source are required.'],
        ];

        foreach ($requiredDocumentTypes as $type) {
            $checks[] = ['label' => $type->name, 'passed' => $customer->documents()->where('type', $type->code)->whereNotNull('verified_at')->exists(), 'detail' => 'A verified '.$type->name.' document is required by KYC configuration.'];
        }

        $completed = collect($checks)->where('passed', true)->count();

        return ['ready' => $completed === count($checks), 'completed' => $completed, 'total' => count($checks), 'checks' => $checks];
    }
}
