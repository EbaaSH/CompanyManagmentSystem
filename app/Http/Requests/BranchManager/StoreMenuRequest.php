<?php

namespace App\Http\Requests\BranchManager;

use App\Http\Responses\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 🔹 Menu
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',

            // 🔹 Categories
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:150',

            // 🔹 Items
            'categories.*.items' => 'required|array|min:1',
            'categories.*.items.*.name' => 'required|string|max:150',
            'categories.*.items.*.description' => 'nullable|string',
            'categories.*.items.*.image_url' => 'nullable|string|max:255',
            'categories.*.items.*.price' => 'required|numeric|min:0',
            'categories.*.items.*.preparation_time_minutes' => 'required|integer|min:1',

            // 🔹 Option Groups
            'categories.*.items.*.option_groups' => 'nullable|array',
            'categories.*.items.*.option_groups.*.name' => 'required|string|max:150',
            'categories.*.items.*.option_groups.*.min_select' => 'nullable|integer|min:0',
            'categories.*.items.*.option_groups.*.max_select' => 'nullable|integer|min:1',
            'categories.*.items.*.option_groups.*.is_required' => 'nullable|boolean',

            // 🔹 Options
            'categories.*.items.*.option_groups.*.options' => 'nullable|array|min:1',
            'categories.*.items.*.option_groups.*.options.*.name' => 'required|string|max:150',
            'categories.*.items.*.option_groups.*.options.*.extra_price' => 'nullable|numeric|min:0',
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
