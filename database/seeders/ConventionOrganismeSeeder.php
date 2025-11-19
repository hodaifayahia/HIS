<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CRM\Organisme;
use App\Models\B2B\Convention;
use App\Models\B2B\Annex;
use App\Models\B2B\Avenant;
use App\Models\ContractPercentage;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Specialization;
use App\Models\User;

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

        // Ensure a default user exists because avenants require a creator_id
        $defaultUser = User::firstOrCreate(
            ['email' => 'seeder-default@example.com'],
            ['name' => 'Seeder Default User', 'password' => bcrypt('password')]
        );

        // For each organisme create 1-3 conventions with related annexes, avenants, and contract percentages
        foreach (Organisme::all() as $organisme) {
            $conventionCount = rand(1,3);
            for ($i = 0; $i < $conventionCount; $i++) {
                // pick a status: pending, active, terminated
                $statuses = ['pending', 'active', 'terminated'];
                $status = $statuses[array_rand($statuses)];

                $conventionAttrs = [
                    'organisme_id' => $organisme->id,
                    'name' => $organisme->name . ' Convention ' . ($i+1),
                    'status' => $status,
                ];

                $convention = Convention::firstOrCreate([
                    'organisme_id' => $organisme->id,
                    'name' => $conventionAttrs['name']
                ], $conventionAttrs);

                // If convention is active, set activation_at if not set
                if ($convention->status === 'active' && !$convention->activation_at) {
                    $convention->activation_at = now();
                    $convention->save();
                }

                // Create 1-2 annexes for this convention
                $annexCount = rand(1,2);
                for ($a = 0; $a < $annexCount; $a++) {
                    // Prefer an existing specialization that already has at least one prestation
                    $specId = Prestation::inRandomOrder()->pluck('specialization_id')->first();
                    $specWithPrestation = $specId ? Specialization::find($specId) : null;

                    // If none exists, create a default specialization and a default prestation for it
                    if (! $specWithPrestation) {
                        $specWithPrestation = Specialization::firstOrCreate(
                            ['name' => 'Seeder Default Specialization'],
                            ['service_id' => $defaultService->id, 'created_by' => $defaultUser->id]
                        );

                        Prestation::firstOrCreate([
                            'internal_code' => 'SEED-DEFAULT-PREST-001'
                        ], [
                            'name' => 'Seeder Default Prestation',
                            'service_id' => $defaultService->id,
                            'specialization_id' => $specWithPrestation->id,
                            'type' => 'consultation',
                            'default_payment_type' => 'cash',
                            'public_price' => 0.00,
                            'convenience_prix' => 0.00,
                            'vat_rate' => 0.00,
                            'is_active' => 1,
                        ]);
                    }

                    Annex::firstOrCreate([
                        'convention_id' => $convention->id,
                        'annex_name' => $convention->name . ' Annex ' . ($a+1)
                    ], [
                        'convention_id' => $convention->id,
                        'annex_name' => $convention->name . ' Annex ' . ($a+1),
                        'description' => 'Auto-created annex',
                        'is_active' => 1,
                        'min_price' => rand(100,1000),
                        'prestation_prix_status' => 'empty',
                        'service_id' => $defaultService->id,
                        // set created_by for traceability
                        'created_by' => $defaultUser->id,
                    ]);
                }

                // Create 0-2 avenants BUT enforce business rules:
                // - Only create an avenant if the convention is active (activation_at set or status === 'active')
                // - Do not create more than one pending/active/scheduled avenant for the same convention
                $avenantCount = rand(0,2);
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
                        'description' => 'Avenant ' . ($av+1) . ' for ' . $convention->name
                    ], [
                        'convention_id' => $convention->id,
                        'description' => 'Avenant ' . ($av+1) . ' for ' . $convention->name,
                        'status' => 'pending',
                        'creator_id' => $defaultUser->id,
                    ]);
                }

                // Create 1-3 contract percentages
                $cpCount = rand(1,3);
                for ($c = 0; $c < $cpCount; $c++) {
                    ContractPercentage::firstOrCreate([
                        'contract_id' => $convention->id,
                        'percentage' => rand(1,100)
                    ], [
                        'contract_id' => $convention->id,
                        'percentage' => rand(1,100)
                    ]);
                }
            }
        }
    }
}
