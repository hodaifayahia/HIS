<?php
// database/seeders/DoctorSeeder.php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 75 doctors with various states
        Doctor::factory()
            ->count(50)
            ->active()
            ->create();

        Doctor::factory()
            ->count(15)
            ->inactive()
            ->create();

        Doctor::factory()
            ->count(10)
            ->create(); // Random state
    }
}