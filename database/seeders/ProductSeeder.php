<?php
// database/seeders/ProductSeeder.php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 100 products with various states
        Product::factory()
            ->count(60)
            ->active()
            ->create();

        Product::factory()
            ->count(20)
            ->clinical()
            ->create();

        Product::factory()
            ->count(15)
            ->requiresApproval()
            ->create();

        Product::factory()
            ->count(5)
            ->create(); // Random state
    }
}