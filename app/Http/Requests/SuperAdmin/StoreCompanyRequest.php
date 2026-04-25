<?php

namespace App\Http\Requests\SuperAdmin;

use App\Http\Responses\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            // Company Info
            'name' => 'required|string|max:150',
            'legal_name' => 'required|string|max:150',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required|string|max:30',
            'status' => 'nullable|in:active,inactive',

            // Manager Info
            'manager_name' => 'required|string|max:150',
            'manager_phone' => 'required|string|max:20',
            'manager_email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            // Company Info
            'name.required' => 'Company name is required.',
            'name.string' => 'Company name must be a valid text.',
            'name.max' => 'Company name cannot exceed 150 characters.',

            'legal_name.required' => 'Legal name is required.',
            'legal_name.string' => 'Legal name must be a valid text.',
            'legal_name.max' => 'Legal name cannot exceed 150 characters.',

            'email.required' => 'Company email is required.',
            'email.email' => 'Please provide a valid company email.',
            'email.unique' => 'This company email is already in use.',

            'phone.required' => 'Company phone is required.',
            'phone.string' => 'Company phone must be a valid value.',
            'phone.max' => 'Company phone cannot exceed 30 characters.',

            'status.in' => 'Status must be either active or inactive.',

            // Manager Info
            'manager_name.required' => 'Manager name is required.',
            'manager_name.string' => 'Manager name must be valid text.',
            'manager_name.max' => 'Manager name cannot exceed 150 characters.',

            'manager_phone.required' => 'Manager phone is required.',
            'manager_phone.string' => 'Manager phone must be valid.',
            'manager_phone.max' => 'Manager phone cannot exceed 20 characters.',

            'manager_email.required' => 'Manager email is required.',
            'manager_email.email' => 'Please provide a valid manager email.',
            'manager_email.unique' => 'This manager email is already registered.',

            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException(
            $validator,
            Response::Validation([], $validator->errors())
        );
    }
}
