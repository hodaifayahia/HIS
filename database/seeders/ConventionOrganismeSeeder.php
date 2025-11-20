<?php

namespace Database\Seeders;

use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\B2B\Convention;
use App\Models\B2B\ConventionDetail;
use App\Models\CONFIGURATION\Service;
use App\Models\ContractPercentage;
use App\Models\CRM\Organisme;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConventionOrganismeSeeder extends Seeder
{
    public function run()
    {
        // Ensure a default service exists because annexes require a service_id
        $defaultService = Service::firstOrCreate(
            ['name' => 'Seeder Default Service'],
            [
                'description' => 'Auto-created default service for seeders',
                'image_url' => 'https://example.com/default-service.png',
                'agmentation' => 0.00,
                'is_active' => 1,
            ]
        );

        // Ensure a default user exists because avenants and conventions require a creator_id
        $defaultUser = User::firstOrCreate(
            ['email' => 'seeder-default@example.com'],
            ['name' => 'Seeder Default User', 'password' => bcrypt('password')]
        );

        // Get all available services for annexes
        $services = Service::where('is_active', true)->get();
        if ($services->isEmpty()) {
            $services = collect([$defaultService]);
        }

        // For each organisme create 1-3 conventions with related details, annexes, and contract percentages
        foreach (Organisme::all() as $organisme) {
            $conventionCount = rand(1, 3);
            for ($i = 0; $i < $conventionCount; $i++) {
                // Pick a status: pending, active, terminated
                $statuses = ['pending', 'active', 'terminated'];
                $status = $statuses[array_rand($statuses)];

                $conventionAttrs = [
                    'organisme_id' => $organisme->id,
                    'name' => $organisme->name.' Convention '.($i + 1),
                    'status' => $status,
                ];

                // Create or get the convention
                $convention = Convention::firstOrCreate([
                    'organisme_id' => $organisme->id,
                    'name' => $conventionAttrs['name'],
                ], $conventionAttrs);

                // If convention is active, set activation_at if not set
                if ($convention->status === 'active' && ! $convention->activation_at) {
                    $convention->activation_at = now();
                    $convention->save();
                }

                // Create ConventionDetail for this convention
                $discountPercentage = rand(10, 90); // Random discount between 10-90%
                $minPrice = rand(50, 500); // Random min price
                $maxPrice = rand(500, 5000); // Random max price

                $startDate = now()->subDays(rand(0, 365)); // Random start date within past year
                $endDate = (clone $startDate)->addYear(); // 1 year from start

                ConventionDetail::firstOrCreate([
                    'convention_id' => $convention->id,
                    'head' => true,
                ], [
                    'convention_id' => $convention->id,
                    'head' => true,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'family_auth' => json_encode(['Conjoint', 'Descendant', 'Ascendant']),
                    'max_price' => $maxPrice,
                    'min_price' => $minPrice,
                    'discount_percentage' => $discountPercentage,
                    'avenant_id' => null, // Initially no avenant
                    'extension_count' => 0,
                ]);

                // Create 1-3 contract percentages for this convention
                $cpCount = rand(1, 3);
                for ($c = 0; $c < $cpCount; $c++) {
                    $percentage = rand(1, 100);
                    ContractPercentage::firstOrCreate([
                        'contract_id' => $convention->id,
                        'percentage' => $percentage,
                    ], [
                        'contract_id' => $convention->id,
                        'percentage' => $percentage,
                    ]);
                }

                // Create 1-2 annexes for this convention
                $annexCount = rand(1, 2);
                for ($a = 0; $a < $annexCount; $a++) {
                    // Pick a random service
                    $selectedService = $services->random();

                    // Randomly choose prestation_prix_status
                    $prixStatuses = ['empty', 'convenience_prix', 'public_prix'];
                    $prixStatus = $prixStatuses[array_rand($prixStatuses)];

                    Annex::firstOrCreate([
                        'convention_id' => $convention->id,
                        'annex_name' => $convention->name.' Annex '.($a + 1),
                    ], [
                        'convention_id' => $convention->id,
                        'annex_name' => $convention->name.' Annex '.($a + 1),
                        'description' => 'Auto-created annex for '.$selectedService->name,
                        'is_active' => 1,
                        'min_price' => rand(100, 1000),
                        'prestation_prix_status' => $prixStatus,
                        'service_id' => $selectedService->id,
                        'created_by' => $defaultUser->id,
                        'updated_by' => $defaultUser->id,
                    ]);
                }

                // Create 0-2 avenants BUT enforce business rules:
                // - Only create an avenant if the convention is active
                // - Do not create more than one pending/active/scheduled avenant for the same convention
                $avenantCount = rand(0, 2);
                for ($av = 0; $av < $avenantCount; $av++) {
                    // Skip creating avenants for non-active conventions
                    if ($convention->status !== 'active' && ! $convention->activation_at) {
                        continue;
                    }

                    // If an active/pending/scheduled avenant already exists, don't create another
                    $existingAvenant = Avenant::where('convention_id', $convention->id)
                        ->whereIn('status', ['pending', 'active', 'scheduled'])
                        ->first();

                    if ($existingAvenant) {
                        continue;
                    }

                    Avenant::firstOrCreate([
                        'convention_id' => $convention->id,
                        'description' => 'Avenant '.($av + 1).' for '.$convention->name,
                    ], [
                        'convention_id' => $convention->id,
                        'description' => 'Avenant '.($av + 1).' for '.$convention->name,
                        'status' => 'pending',
                        'creator_id' => $defaultUser->id,
                    ]);
                }
            }
        }

        $this->command->info('✅ Created conventions with details for all organisms!');
    }
}
