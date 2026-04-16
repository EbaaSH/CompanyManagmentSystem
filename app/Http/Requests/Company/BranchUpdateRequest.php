<?php

namespace App\Http\Requests\Company;

use App\Http\Responses\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BranchUpdateRequest extends FormRequest
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
            'name' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:50|unique:branches,code,'.$this->route('branchId'),
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',

            'weekly_schedule' => 'nullable|array|min:1',
            'weekly_schedule.*.day' => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'weekly_schedule.*.opening_time' => 'nullable|date_format:H:i',
            'weekly_schedule.*.closing_time' => 'nullable|date_format:H:i',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, Response::Validation([], $validator->errors()));
    }
}
