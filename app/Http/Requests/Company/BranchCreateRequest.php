<?php

namespace App\Http\Requests\Company;

use App\Http\Responses\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BranchCreateRequest extends FormRequest
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
            // Branch fields
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'phone' => 'required|string|max:30',
            'is_active' => 'required|boolean',

            // Weekly schedule
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'weekly_schedule.*.opening_time' => 'required|date_format:H:i',
            'weekly_schedule.*.closing_time' => 'required|date_format:H:i',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, Response::Validation([], $validator->errors()));
    }
}
