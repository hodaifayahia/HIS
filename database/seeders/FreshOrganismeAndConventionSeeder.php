<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CRM\Organisme;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Specialization;

class FreshOrganismeAndConventionSeeder extends Seeder
{
    public function run()
    {
        // Truncate organismes table (use delete to respect foreign keys in tests/database)
        DB::table('organismes')->delete();
        // Ensure a default service exists (used by Annex seeder)
        $defaultService = Service::firstOrCreate(
            ['name' => 'Seeder Default Service'],
            [
                'description' => 'Auto-created default service for seeders',
                'image_url' => 'https://example.com/default-service.png',
                'agmentation' => 0.00,
                'is_active' => 1,
            ]
        );

        // Create sample specializations (used by prestations)
        $spec = Specialization::firstOrCreate(['name' => 'General Medicine'], ['service_id' => $defaultService->id]);

        // Create example prestations linked to the default service
        Prestation::firstOrCreate(['internal_code' => 'PREST-CONS-001'], [
            'name' => 'Consultation',
            'service_id' => $defaultService->id,
            'specialization_id' => $spec->id,
            'type' => 'consultation',
            'default_payment_type' => 'cash',
            'public_price' => 100.00,
            'convenience_prix' => 0.00,
            'vat_rate' => 19.00,
            'is_active' => 1,
        ]);

        Prestation::firstOrCreate(['internal_code' => 'PREST-BT-001'], [
            'name' => 'Blood Test',
            'service_id' => $defaultService->id,
            'specialization_id' => $spec->id,
            'type' => 'laboratory',
            'default_payment_type' => 'cash',
            'public_price' => 50.00,
            'convenience_prix' => 0.00,
            'vat_rate' => 19.00,
            'is_active' => 1,
        ]);

        // Create a few known organismes
        $organismes = [
            ['name' => 'Alpha Health', 'email' => 'alpha@example.com'],
            ['name' => 'Beta Care', 'email' => 'beta@example.com'],
            ['name' => 'Gamma Assurance', 'email' => 'gamma@example.com'],
        ];

        foreach ($organismes as $o) {
            Organisme::create($o);
        }

        // Run the convention seeder to populate conventions + related data
        $this->call(ConventionOrganismeSeeder::class);
    }
}
