<?php
// database/seeders/AllergySeeder.php

namespace Database\Seeders;

use App\Models\Allergy;
use Illuminate\Database\Seeder;

class AllergySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 80 allergies with various severities
        Allergy::factory()
            ->count(40)
            ->mild()
            ->create();

        Allergy::factory()
            ->count(25)
            ->create(); // Random severity

        Allergy::factory()
            ->count(15)
            ->severe()
            ->create();
    }
}