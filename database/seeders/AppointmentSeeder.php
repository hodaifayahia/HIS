<?php
// database/seeders/AppointmentSeeder.php

namespace Database\Seeders;

use App\Models\Appointment;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 150 appointments
        Appointment::factory()
            ->count(150)
            ->create();
    }
}