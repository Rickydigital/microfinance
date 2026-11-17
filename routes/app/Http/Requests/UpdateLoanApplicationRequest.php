<?php

namespace App\Http\Requests;

use App\LoanApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('loan_application');

        return ($this->user()?->can('loan.application.update') ?? false)
            && in_array($application?->status, [LoanApplicationStatus::Draft, LoanApplicationStatus::UnderAssessment], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return (new StoreLoanApplicationRequest)->rules();
    }
}
