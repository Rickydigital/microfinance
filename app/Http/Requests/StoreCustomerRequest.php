<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('customer.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            'identification_number' => ['nullable', 'string', 'max:100', 'unique:customers,identification_number'],
            'assigned_officer_id' => ['nullable', 'exists:users,id'],
            'kyc_risk_rating' => ['nullable', 'in:Low,Medium,High'],
        ];
    }
}
