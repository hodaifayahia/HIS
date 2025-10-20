<?php
// database/seeders/ServiceSeeder.php

namespace Database\Seeders;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create only 12 services since we have limited unique service names
        Service::factory()
            ->count(10)
            ->active()
            ->create();

        Service::factory()
            ->count(2)
            ->inactive()
            ->create();
    }
}