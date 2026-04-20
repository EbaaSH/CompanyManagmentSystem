<?php

namespace App\Http\Requests\CompanyManager;

use App\Http\Responses\Response;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // Branch Manager
            'name' => 'nullable|string|max:150',
            'email' => 'nullable|email|unique:users,email,'.optional($this->branch?->user)->id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:30',

            // Branch Info
            'code' => 'nullable|string|max:50|unique:branches,code,'.$this->route('branchId'),
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',

            // Weekly Schedule
            'weekly_schedule' => 'nullable|array',
            'weekly_schedule.*.day' => 'required|string|exists:week_days,day_name',
            'weekly_schedule.*.opening_time' => 'required|date_format:H:i',
            'weekly_schedule.*.closing_time' => 'required|date_format:H:i|after:weekly_schedule.*.opening_time',

            // Employees
            'employees' => 'nullable|array',
            'employees.*.id' => 'nullable|exists:users,id',

            'employees.*.name' => 'required_with:employees|string|max:150',
            'employees.*.email' => [
                'required_with:employees',
                'email',
                function ($attribute, $value, $fail) {
                    $id = request()->input(str_replace('email', 'id', $attribute));
                    $exists = User::where('email', $value)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'employees.*.password' => 'nullable|string|min:8',
            'employees.*.phone' => 'nullable|string|max:30',
            'employees.*.job_title_id' => 'nullable|exists:job_titles,id',
            'employees.*.shift_id' => 'nullable|exists:shifts,id',
            'employees.*.hire_date' => 'nullable|date',
            'employees.*.is_active' => 'nullable|boolean',

            // Drivers
            'drivers' => 'nullable|array',
            'drivers.*.id' => 'nullable|exists:users,id',

            'drivers.*.name' => 'required_with:drivers|string|max:150',
            'drivers.*.email' => [
                'required_with:drivers',
                'email',
                function ($attribute, $value, $fail) {
                    $id = request()->input(str_replace('email', 'id', $attribute));
                    $exists = User::where('email', $value)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'drivers.*.password' => 'nullable|string|min:8',
            'drivers.*.phone' => 'nullable|string|max:30',
            'drivers.*.vehicle_type' => 'nullable|string|max:100',
            'drivers.*.plate_number' => 'nullable|string|max:50',
            'drivers.*.availability_status' => 'nullable|in:online,offline,busy',
            'drivers.*.is_active' => 'nullable|boolean',
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
