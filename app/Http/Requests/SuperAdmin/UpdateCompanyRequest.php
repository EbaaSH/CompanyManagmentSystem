<?php

namespace App\Http\Requests\SuperAdmin;

use App\Http\Responses\Response;
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
            'phone' => 'sometimes|string|max:30',

            // Manager Info
            'manager_name' => 'sometimes|string|max:150',
            'manager_phone' => 'sometimes|string|max:20',
            'manager_email' => "sometimes|email|unique:users,email,{$this->user()->id}",
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
