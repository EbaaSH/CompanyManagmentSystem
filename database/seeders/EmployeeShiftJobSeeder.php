<?php

namespace Database\Seeders;

use App\Models\Employee\JobTitle;
use App\Models\Employee\Shift;
use Illuminate\Database\Seeder;

class EmployeeShiftJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobTitles = [
            ['job_title' => 'Cashier'],
            ['job_title' => 'Cook'],
            ['job_title' => 'Shift Leader'],
            ['job_title' => 'Assistant Manager'],
            ['job_title' => 'Store Manager'],
            ['job_title' => 'Delivery Driver'],
            ['job_title' => 'Cleaner'],
            ['job_title' => 'Customer Service Representative'],
        ];

        foreach ($jobTitles as $jobTitle) {
            JobTitle::firstOrCreate([
                'job_title' => $jobTitle['job_title'],
            ]);
        }

        $shifts = [
            [
                'shift_name' => 'Morning Shift',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
            ],
            [
                'shift_name' => 'Evening Shift',
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
            ],
            [
                'shift_name' => 'Night Shift',
                'start_time' => '00:00:00',
                'end_time' => '08:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['shift_name' => $shift['shift_name']],
                [
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                ]
            );
        }
    }
}
