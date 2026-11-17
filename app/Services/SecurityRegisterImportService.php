<?php

namespace App\Services;

use App\Models\Collateral;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\NumberSequence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SecurityRegisterImportService
{
    public const GUARANTOR_HEADERS = ['customer_number', 'first_name', 'middle_name', 'last_name', 'phone', 'identification_type', 'identification_number', 'occupation', 'monthly_income'];

    public const COLLATERAL_HEADERS = ['customer_number', 'type', 'description', 'ownership_reference', 'market_value', 'forced_sale_value', 'valuer', 'valuation_date'];

    public function import(string $type, string $path, User $user): int
    {
        $handle = fopen($path, 'r');
        $headers = array_map('trim', fgetcsv($handle) ?: []);
        $expected = $type === 'guarantors' ? self::GUARANTOR_HEADERS : self::COLLATERAL_HEADERS;
        if ($headers !== $expected) {
            throw ValidationException::withMessages(['file' => 'The CSV columns do not match the downloaded sample.']);
        }
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) > 0) {
                $rows[] = array_combine($headers, array_pad($values, count($headers), ''));
            }
        }
        fclose($handle);
        if (count($rows) > 1000) {
            throw ValidationException::withMessages(['file' => 'A maximum of 1,000 rows is allowed.']);
        }

        return DB::transaction(function () use ($rows, $type, $user): int {
            foreach ($rows as $index => $row) {
                $customer = Customer::query()->where('customer_number', $row['customer_number'])->when($user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))->first();
                if (! $customer) {
                    throw ValidationException::withMessages(['file' => 'Row '.($index + 2).': customer number was not found in your branch.']);
                }
                if ($type === 'guarantors') {
                    Validator::make($row, ['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:30'], 'identification_number' => ['required', 'string', 'max:100', Rule::unique('guarantors')], 'monthly_income' => ['required', 'numeric', 'min:0']])->validate();
                    Guarantor::query()->create($row + ['principal_customer_id' => $customer->id, 'status' => 'Pending Verification']);
                } else {
                    Validator::make($row, ['type' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:500'], 'market_value' => ['required', 'numeric', 'gt:0'], 'forced_sale_value' => ['required', 'numeric', 'gt:0', 'lte:market_value'], 'valuation_date' => ['nullable', 'date']])->validate();
                    Collateral::query()->create($row + ['customer_id' => $customer->id, 'branch_id' => $customer->branch_id, 'collateral_number' => NumberSequence::issue('collateral'), 'status' => 'Proposed']);
                }
            }

            return count($rows);
        });
    }
}
