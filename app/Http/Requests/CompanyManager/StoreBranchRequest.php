<?php

namespace App\Http\Requests\CompanyManager;

use App\Http\Responses\Response;
use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('company-manager');
    }

    public function rules(): array
    {
        return [
            // Branch Manager
            'manager_name' => 'required|string|max:150',
            'manager_email' => 'required|email|unique:users,email',
            'manager_phone' => [
                'required',
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
            'password' => 'required|string|min:8',

            // Branch Info
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone' => 'nullable|string|max:30|phone:ALL',
            'is_active' => 'nullable|boolean',

            // Weekly Schedule
            'weekly_schedule' => 'nullable|array',
            'weekly_schedule.*.day' => 'required|string|exists:week_days,day_name',
            'weekly_schedule.*.opening_time' => 'required|date_format:H:i',
            'weekly_schedule.*.closing_time' => 'required|date_format:H:i|after:weekly_schedule.*.opening_time',

            // Employees
            'employees' => 'nullable|array',
            'employees.*.name' => 'required|string|max:150',
            'employees.*.email' => 'required|email|unique:users,email',
            'employees.*.password' => 'required|string|min:8',
            'employees.*.phone' => [
                'required',
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
            'employees.*.job_title_id' => 'nullable|exists:job_titles,id',
            'employees.*.shift_id' => 'nullable|exists:shifts,id',
            'employees.*.hire_date' => 'nullable|date',
            'employees.*.is_active' => 'nullable|boolean',

            // Drivers
            'drivers' => 'nullable|array',
            'drivers.*.name' => 'required|string|max:150',
            'drivers.*.email' => 'required|email|unique:users,email',
            'drivers.*.password' => 'required|string|min:8',
            'drivers.*.phone' => [
                'required',
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
            'drivers.*.vehicle_type' => 'nullable|string|max:100',
            'drivers.*.plate_number' => 'nullable|string|max:50',
            'drivers.*.availability_status' => 'nullable|in:online,offline,busy,available',
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
