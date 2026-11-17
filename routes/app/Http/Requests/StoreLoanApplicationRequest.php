<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('loan.application.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'loan_product_version_id' => ['required', 'exists:loan_product_versions,id'],
            'requested_amount' => ['required', 'numeric', 'gt:0'],
            'requested_term' => ['required', 'integer', 'min:1'],
            'repayment_frequency' => ['required', 'in:Daily,Weekly,Biweekly,Monthly'],
            'purpose' => ['required', 'string', 'max:2000'],
            'declared_monthly_income' => ['required', 'numeric', 'min:0'],
            'declared_monthly_expenses' => ['required', 'numeric', 'min:0'],
            'existing_debt_payment' => ['required', 'numeric', 'min:0'],
        ];
    }
}
