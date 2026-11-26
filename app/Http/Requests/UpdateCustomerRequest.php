<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $kin = collect(is_array($this->input('kin')) ? $this->input('kin') : [])
            ->filter(fn ($item): bool => is_array($item) && collect($item)->except('id')->contains(fn ($value): bool => $value !== null && trim((string) $value) !== ''))
            ->unique(function (array $item): string {
                return strtolower(trim((string) ($item['full_name'] ?? '')).'|'.trim((string) ($item['phone'] ?? '')).'|'.trim((string) ($item['identification_number'] ?? '')));
            })
            ->take(5)
            ->values()
            ->all();

        $this->merge(['kin' => $kin]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('customer.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address' => ['nullable', 'array'],
            'address.region_id' => ['nullable', 'required_with:address.district_id', 'exists:regions,id'],
            'address.district_id' => ['nullable', 'required_with:address.region_id', 'exists:districts,id'],
            'address.ward_id' => ['nullable', 'required_with:address.region_id', 'exists:wards,id'],
            'address.street_id' => ['nullable', 'required_with:address.region_id', 'exists:streets,id'],
            'address.description' => ['nullable', 'string', 'max:1000'],
            'economic' => ['nullable', 'array'],
            'economic.occupation' => ['nullable', 'string', 'max:150'],
            'economic.employer_or_business' => ['nullable', 'string', 'max:255'],
            'economic.income_source' => ['nullable', 'string', 'max:1000'],
            'economic.monthly_income' => ['nullable', 'numeric', 'min:0'],
            'economic.monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'economic.existing_debt_payment' => ['nullable', 'numeric', 'min:0'],
            'economic.business_turnover' => ['nullable', 'numeric', 'min:0'],
            'kin' => ['nullable', 'array', 'max:5'],
            'kin.*.id' => ['nullable', 'integer', 'exists:customer_relationships,id'],
            'kin.*.full_name' => ['required_with:kin.*.phone', 'nullable', 'string', 'max:255'],
            'kin.*.phone' => ['nullable', 'string', 'max:30'],
            'kin.*.address' => ['nullable', 'string', 'max:500'],
            'kin.*.identification_number' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::exists('customer_types', 'code')->where('is_active', true)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'identification_type' => ['nullable', Rule::exists('identification_types', 'code')->where(function ($query): void {
                $query->where('is_active', true)->where(function ($scope): void {
                    $scope->whereNull('customer_type_code')->orWhere('customer_type_code', $this->string('type')->toString());
                });
            })],
            'identification_number' => ['nullable', 'string', 'max:100', 'unique:customers,identification_number,'.$this->route('customer')->id],
            'assigned_officer_id' => ['nullable', 'exists:users,id'],
            'kyc_risk_rating' => ['nullable', 'in:Low,Medium,High'],
        ];
    }
}
