<?php
// database/seeders/ConventionSeeder.php

namespace Database\Seeders;

use App\Models\B2B\Convention;
use Illuminate\Database\Seeder;

class ConventionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 70 conventions with various states
        Convention::factory()
            ->count(30)
            ->active()
            ->create();

        Convention::factory()
            ->count(15)
            ->draft()
            ->create();

        Convention::factory()
            ->count(15)
            ->expired()
            ->create();

        Convention::factory()
            ->count(10)
            ->create(); // Random state
    }
}