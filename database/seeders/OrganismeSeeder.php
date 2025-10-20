<?php
// database/seeders/OrganismeSeeder.php

namespace Database\Seeders;

use App\Models\CRM\Organisme;
use Illuminate\Database\Seeder;

class OrganismeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 60 organismes with various configurations
        Organisme::factory()
            ->count(50)
            ->create();

        // Create some specific organismes for major wilayas
        $majorWilayas = ['Alger', 'Oran', 'Constantine', 'Annaba', 'Blida'];
        
        foreach ($majorWilayas as $wilaya) {
            Organisme::factory()
                ->count(2)
                ->inWilaya($wilaya)
                ->create();
        }
    }
}