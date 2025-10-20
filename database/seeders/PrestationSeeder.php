<?php
// database/seeders/PrestationSeeder.php

namespace Database\Seeders;

use App\Models\CONFIGURATION\Prestation;
use Illuminate\Database\Seeder;

class PrestationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 80 prestations with various types
        Prestation::factory()
            ->count(30)
            ->consultation()
            ->create();

        Prestation::factory()
            ->count(25)
            ->intervention()
            ->create();

        Prestation::factory()
            ->count(15)
            ->reimbursable()
            ->create();

        Prestation::factory()
            ->count(10)
            ->create(); // Random state
    }
}