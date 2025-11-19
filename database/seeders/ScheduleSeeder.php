<?php
// database/seeders/ScheduleSeeder.php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 150 schedules with various shifts
        Schedule::factory()
            ->count(60)
            ->morningShift()
            ->create();

        Schedule::factory()
            ->count(50)
            ->afternoonShift()
            ->create();

        Schedule::factory()
            ->count(40)
            ->active()
            ->create(); // Random shifts but active
    }
}