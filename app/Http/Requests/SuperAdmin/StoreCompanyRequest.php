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

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException(
            $validator,
            Response::Validation([], $validator->errors())
        );
    }
}
