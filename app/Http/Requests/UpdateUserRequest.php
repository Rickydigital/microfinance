<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('admin.user.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'staff_number' => ['required', 'string', 'max:50', 'unique:users,staff_number,'.$this->route('user')->id],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone,'.$this->route('user')->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->route('user')->id],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
