<?php

namespace App\Http\Requests\BranchManager;

use App\Http\Responses\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('menus.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',

            'categories' => 'required|array|min:1',

            'categories.*.id' => 'nullable|exists:menu_categories,id',
            'categories.*.name' => 'required|string|max:255',

            'categories.*.items' => 'required|array|min:1',

            'categories.*.items.*.id' => 'nullable|exists:menu_items,id',
            'categories.*.items.*.name' => 'required|string|max:255',
            'categories.*.items.*.description' => 'nullable|string',

            // 🔥 IMAGE VALIDATION
            'categories.*.items.*.image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'categories.*.items.*.price' => 'required|numeric|min:0',
            'categories.*.items.*.preparation_time_minutes' => 'required|integer|min:1',

            'categories.*.items.*.option_groups' => 'nullable|array',
            'categories.*.items.*.option_groups.*.id' => 'nullable|exists:item_option_groups,id',
            'categories.*.items.*.option_groups.*.name' => 'required|string|max:255',

            'categories.*.items.*.option_groups.*.options' => 'nullable|array',
            'categories.*.items.*.option_groups.*.options.*.id' => 'nullable|exists:item_options,id',
            'categories.*.items.*.option_groups.*.options.*.name' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Menu name is required',

            'categories.required' => 'At least one category is required',

            'categories.*.name.required' => 'Category name is required',

            'categories.*.items.required' => 'Each category must have items',

            'categories.*.items.*.name.required' => 'Item name is required',

            'categories.*.items.*.image.image' => 'Item image must be an image file',
            'categories.*.items.*.image.mimes' => 'Image must be jpg, jpeg or png',
            'categories.*.items.*.image.max' => 'Image size must not exceed 2MB',

            'categories.*.items.*.price.required' => 'Item price is required',

            'categories.*.items.*.preparation_time_minutes.required' => 'Preparation time is required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, Response::Validation([], $validator->errors()));
    }
}
