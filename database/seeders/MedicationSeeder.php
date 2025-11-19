<?php
// database/seeders/MedicationSeeder.php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 120 medications with various types
        Medication::factory()
            ->count(40)
            ->antibiotic()
            ->create();

        Medication::factory()
            ->count(30)
            ->painkiller()
            ->create();

        Medication::factory()
            ->count(50)
            ->create(); // Random types
    }
}