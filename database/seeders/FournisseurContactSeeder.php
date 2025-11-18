<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use App\Models\FournisseurContact;
use Illuminate\Database\Seeder;

class FournisseurContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all existing fournisseurs
        $fournisseurs = Fournisseur::all();

        // For each supplier, create 2-5 contacts
        foreach ($fournisseurs as $fournisseur) {
            // Create 1 primary contact and 1-4 secondary contacts
            FournisseurContact::factory()
                ->count(rand(2, 5))
                ->for($fournisseur)
                ->create();

            // Ensure at least one primary contact per supplier
            if ($fournisseur->contacts->where('is_primary', true)->isEmpty()) {
                $fournisseur->contacts()->first()->update(['is_primary' => true]);
            }
        }
    }
}
