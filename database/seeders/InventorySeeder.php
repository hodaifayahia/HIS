<?php
// database/seeders/InventorySeeder.php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 200 inventory records with various states
        Inventory::factory()
            ->count(150)
            ->create(); // Normal stock

        Inventory::factory()
            ->count(30)
            ->lowStock()
            ->create();

        Inventory::factory()
            ->count(20)
            ->expired()
            ->create();
    }
}