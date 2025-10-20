<?php
// database/seeders/PatientSeeder.php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 100 patients
        Patient::factory()
            ->count(100)
            ->create();
    }
}