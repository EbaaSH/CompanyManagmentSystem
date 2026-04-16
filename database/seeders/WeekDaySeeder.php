<?php

namespace Database\Seeders;

use App\Models\Company\WeekDay;
use Illuminate\Database\Seeder;

class WeekDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($weekDays as $day) {
            WeekDay::create(['day_name' => $day]);
        }
    }
}
