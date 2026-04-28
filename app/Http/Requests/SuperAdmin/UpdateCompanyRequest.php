<?php

namespace App\Http\Requests\SuperAdmin;

use App\Http\Responses\Response;
use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        $companyId = $this->route('id');

        return [
            // Company Info
            'name' => 'sometimes|string|max:150',
            'legal_name' => 'sometimes|string|max:150',
            'email' => "sometimes|email|unique:companies,email,{$companyId}",
            'phone' => [
                'sometimes',
                'string',
                'phone:ALL',
                'max:30',
                function ($attribute, $value, $fail) {
                    $existsInUsers = User::where('phone', $value)->exists();
                    $existsInCompanies = Company::where('phone', $value)->exists();

                    if ($existsInUsers || $existsInCompanies) {
                        $fail('This phone number is already in use.');
                    }
                },
            ],
            'status' => 'sometimes|in:active,inactive',

            // Manager Info
            'manager_name' => 'sometimes|string|max:150',
            'manager_phone' => [
                'sometimes',
                'string',
                'phone:ALL',
                'max:30',
                function ($attribute, $value, $fail) {
                    $existsInUsers = User::where('phone', $value)->exists();
                    $existsInCompanies = Company::where('phone', $value)->exists();

                    if ($existsInUsers || $existsInCompanies) {
                        $fail('This phone number is already in use.');
                    }
                },
            ],
            'manager_email' => "sometimes|email|unique:users,email,{$this->user()->id}",
            'password' => 'sometimes|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            // Company Info
            'name.string' => 'Company name must be valid text.',
            'name.max' => 'Company name cannot exceed 150 characters.',

            'legal_name.string' => 'Legal name must be valid text.',
            'legal_name.max' => 'Legal name cannot exceed 150 characters.',

            'email.email' => 'Please provide a valid company email.',
            'email.unique' => 'This company email is already in use.',

            'phone.string' => 'Company phone must be valid.',
            'phone.max' => 'Company phone cannot exceed 30 characters.',

            // Manager Info
            'manager_name.string' => 'Manager name must be valid text.',
            'manager_name.max' => 'Manager name cannot exceed 150 characters.',

            'manager_phone.string' => 'Manager phone must be valid.',
            'manager_phone.max' => 'Manager phone cannot exceed 20 characters.',

            'manager_email.email' => 'Please provide a valid manager email.',
            'manager_email.unique' => 'This manager email is already registered.',

            'password.string' => 'Password must be valid text.',
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
