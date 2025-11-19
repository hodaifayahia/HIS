<?php
// database/seeders/BonCommendSeeder.php

namespace Database\Seeders;

use App\Models\BonCommend;
use Illuminate\Database\Seeder;

class BonCommendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 100 bon commends with various states
        BonCommend::factory()
            ->count(40)
            ->approved()
            ->create();

        BonCommend::factory()
            ->count(35)
            ->pending()
            ->create();

        BonCommend::factory()
            ->count(25)
            ->create(); // Random state
    }
}