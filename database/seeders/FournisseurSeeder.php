<?php
// database/seeders/FournisseurSeeder.php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 60 suppliers with various states
        Fournisseur::factory()
            ->count(50)
            ->active()
            ->create();

        Fournisseur::factory()
            ->count(10)
            ->inactive()
            ->create();
    }
}