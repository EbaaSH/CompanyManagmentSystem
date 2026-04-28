<?php

namespace App\Http\Requests\Customer;

use App\Http\Responses\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:30|unique:users,phone|phone:ALL',
            'password' => 'required|string|min:6',

            'addresses' => 'required|array|min:1',
            'addresses.*.label' => 'required|string|max:100',
            'addresses.*.address_line' => 'required|string|max:255',
            'addresses.*.city' => 'required|string|max:100',
            'addresses.*.latitude' => 'required|numeric',
            'addresses.*.longitude' => 'required|numeric',
            'addresses.*.is_default' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, Response::Validation([], $validator->errors()));
    }
}
