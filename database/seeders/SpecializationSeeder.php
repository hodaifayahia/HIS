<?php
// database/seeders/SpecializationSeeder.php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 60 specializations with various states
        Specialization::factory()
            ->count(50)
            ->active()
            ->create();

        Specialization::factory()
            ->count(10)
            ->inactive()
            ->create();
    }
}