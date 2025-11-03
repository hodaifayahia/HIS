<?php

namespace Database\Seeders;

use App\Models\Coffre\Caisse;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;

class CaisseSeeder extends Seeder
{
    /**
     * Seed the caisses (cash registers) table.
     */
    public function run(): void
    {
        // Get services or create default ones if they don't exist
        $services = Service::all();

        if ($services->isEmpty()) {
            $this->command->info('No services found. Creating default services...');
            $serviceNames = [
                'Reception',
                'Consultation',
                'Pharmacy',
                'Laboratory',
                'Imaging',
                'Emergency',
                'Surgery',
                'Pediatrics',
                'Cardiology',
                'Dermatology'
            ];

            foreach ($serviceNames as $name) {
                Service::firstOrCreate(
                    ['name' => $name],
                    [
                        'code' => strtoupper(str_replace(' ', '_', $name)),
                        'description' => 'Service: ' . $name,
                        'is_active' => true
                    ]
                );
            }

            $services = Service::all();
        }

        $caissePrefixes = ['CAISSE', 'REGISTER', 'TILL'];
        $locations = ['Main Hall', 'Reception', 'Ground Floor', 'First Floor', 'Second Floor', 'Pharmacy Wing', 'Emergency Wing'];

        $caisses = [];

        // Create multiple caisses per service for realistic scenario
        $caisseCount = 0;

        foreach ($services as $service) {
            $numCaissesPerService = rand(2, 5); // 2-5 cash registers per service

            for ($i = 0; $i < $numCaissesPerService; $i++) {
                $caisseCount++;
                $prefix = $caissePrefixes[array_rand($caissePrefixes)];

                $caisses[] = [
                    'name' => $prefix . ' - ' . $service->name . ' #' . ($i + 1),
                    'location' => $service->name . ' - ' . $locations[array_rand($locations)],
                    'service_id' => $service->id,
                    'is_active' => rand(0, 1) < 80 ? true : false, // 80% active
                    'created_at' => now()->subDays(rand(1, 90)),
                    'updated_at' => now(),
                ];
            }
        }

        // Batch insert
        foreach (array_chunk($caisses, 50) as $batch) {
            Caisse::insert($batch);
        }

        $this->command->info("✓ Successfully seeded {$caisseCount} cash registers (Caisses)!");
    }
}
