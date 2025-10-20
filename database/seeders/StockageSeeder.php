<?php
// database/seeders/StockageSeeder.php

namespace Database\Seeders;

use App\Models\Stockage;
use Illuminate\Database\Seeder;

class StockageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 80 stockage locations with various types
        Stockage::factory()
            ->count(30)
            ->pharmacy()
            ->create();

        Stockage::factory()
            ->count(40)
            ->active()
            ->create();

        Stockage::factory()
            ->count(10)
            ->create(); // Random state
    }
}