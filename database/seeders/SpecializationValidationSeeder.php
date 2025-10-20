<?php
// database/seeders/SpecializationValidationSeeder.php

namespace Database\Seeders;

use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SpecializationValidationSeeder extends Seeder
{
    /**
     * Run the database seeds with validation.
     */
    public function run(): void
    {
        $this->validateExistingData();
        $this->ensureDataIntegrity();
        $this->createValidatedSpecializations();
    }

    /**
     * Validate existing specialization data
     */
    private function validateExistingData(): void
    {
        $this->command->info('Validating existing specialization data...');
        
        $specializations = Specialization::all();
        $errors = [];

        foreach ($specializations as $specialization) {
            $validator = Validator::make($specialization->toArray(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'photo' => 'nullable|url|max:500',
                'service_id' => 'required|exists:services,id',
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                    'errors' => $validator->errors()->toArray()
                ];
            }
        }

        if (!empty($errors)) {
            $this->command->error('Validation errors found in existing data:');
            foreach ($errors as $error) {
                $this->command->error("Specialization ID {$error['id']} ({$error['name']}): " . json_encode($error['errors']));
            }
        } else {
            $this->command->info('All existing specialization data is valid.');
        }
    }

    /**
     * Ensure data integrity constraints
     */
    private function ensureDataIntegrity(): void
    {
        $this->command->info('Ensuring data integrity...');

        // Check for orphaned specializations (without valid service)
        $orphanedCount = Specialization::whereDoesntHave('service')->count();
        if ($orphanedCount > 0) {
            $this->command->warn("Found {$orphanedCount} specializations without valid services.");
            
            // Assign them to a default service or create one
            $defaultService = Service::firstOrCreate(
                ['name' => 'Service général'],
                [
                    'description' => 'Service général pour spécialisations non assignées',
                    'start_time' => '08:00',
                    'end_time' => '17:00',
                    'is_active' => true
                ]
            );

            Specialization::whereDoesntHave('service')
                ->update(['service_id' => $defaultService->id]);
            
            $this->command->info("Assigned orphaned specializations to default service.");
        }

        // Check for duplicate specialization names
        $duplicates = DB::table('specializations')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->count() > 0) {
            $this->command->warn('Found duplicate specialization names:');
            foreach ($duplicates as $duplicate) {
                $this->command->warn("- {$duplicate->name} ({$duplicate->count} occurrences)");
            }
        }

        // Ensure at least one active specialization per service
        $servicesWithoutActiveSpecs = Service::whereDoesntHave('specializations', function ($query) {
            $query->where('is_active', true);
        })->get();

        foreach ($servicesWithoutActiveSpecs as $service) {
            $this->command->info("Service '{$service->name}' has no active specializations. Creating one...");
            
            Specialization::create([
                'name' => 'Médecine générale - ' . $service->name,
                'description' => 'Spécialité générale pour le service ' . $service->name,
                'service_id' => $service->id,
                'is_active' => true
            ]);
        }
    }

    /**
     * Create additional validated specializations
     */
    private function createValidatedSpecializations(): void
    {
        $this->command->info('Creating additional validated specializations...');

        $validatedSpecializations = [
            [
                'name' => 'Médecine d\'urgence pédiatrique',
                'description' => 'Spécialité d\'urgence dédiée aux enfants et nourrissons',
                'service_name' => 'Urgences',
                'is_active' => true
            ],
            [
                'name' => 'Cardiologie interventionnelle',
                'description' => 'Spécialité cardiologique avec interventions percutanées',
                'service_name' => 'Cardiologie',
                'is_active' => true
            ],
            [
                'name' => 'Chirurgie laparoscopique',
                'description' => 'Chirurgie mini-invasive par laparoscopie',
                'service_name' => 'Chirurgie générale',
                'is_active' => true
            ],
            [
                'name' => 'Radiologie interventionnelle',
                'description' => 'Radiologie avec procédures thérapeutiques guidées par imagerie',
                'service_name' => 'Radiologie',
                'is_active' => true
            ],
            [
                'name' => 'Pharmacovigilance',
                'description' => 'Surveillance et évaluation des effets indésirables des médicaments',
                'service_name' => 'Pharmacie',
                'is_active' => true
            ]
        ];

        foreach ($validatedSpecializations as $spec) {
            $service = Service::where('name', $spec['service_name'])->first();
            
            if (!$service) {
                $this->command->warn("Service '{$spec['service_name']}' not found. Skipping specialization '{$spec['name']}'.");
                continue;
            }

            // Check if specialization already exists
            $existing = Specialization::where('name', $spec['name'])->first();
            if ($existing) {
                $this->command->info("Specialization '{$spec['name']}' already exists. Skipping.");
                continue;
            }

            // Validate before creating
            $validator = Validator::make([
                'name' => $spec['name'],
                'description' => $spec['description'],
                'service_id' => $service->id,
                'is_active' => $spec['is_active']
            ], [
                'name' => 'required|string|max:255|unique:specializations,name',
                'description' => 'nullable|string|max:1000',
                'service_id' => 'required|exists:services,id',
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                $this->command->error("Validation failed for '{$spec['name']}': " . json_encode($validator->errors()->toArray()));
                continue;
            }

            Specialization::create([
                'name' => $spec['name'],
                'description' => $spec['description'],
                'service_id' => $service->id,
                'is_active' => $spec['is_active']
            ]);

            $this->command->info("Created validated specialization: {$spec['name']}");
        }
    }
}