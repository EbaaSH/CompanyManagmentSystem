<?php

namespace App\Http\Requests\Driver;

use App\Http\Responses\Response;
use App\Models\Menu\MenuItem;
use App\Models\Order\Order;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RejectDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('driver');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
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
