<?php

namespace App\Http\Requests\CompanyManager;

use App\Http\Responses\Response;
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
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:30',

            // Branch Info
            'code' => 'required|string|max:50|unique:branches,code',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
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
            'employees.*.name' => 'required|string|max:150',
            'employees.*.email' => 'required|email|unique:users,email',
            'employees.*.password' => 'required|string|min:8',
            'employees.*.phone' => 'required|string|max:30',
            'employees.*.job_title_id' => 'nullable|exists:job_titles,id',
            'employees.*.shift_id' => 'nullable|exists:shifts,id',
            'employees.*.hire_date' => 'nullable|date',
            'employees.*.is_active' => 'nullable|boolean',

            // Drivers
            'drivers' => 'nullable|array',
            'drivers.*.name' => 'required|string|max:150',
            'drivers.*.email' => 'required|email|unique:users,email',
            'drivers.*.password' => 'required|string|min:8',
            'drivers.*.phone' => 'required|string|max:30',
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
