<?php
// database/seeders/SpecializationSeeder.php

namespace Database\Seeders;

use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have services first
        if (Service::count() === 0) {
            $this->call(ServiceSeeder::class);
        }

        // Use database transaction for better performance and data integrity
        DB::transaction(function () {
            // Create core medical specializations with specific service relationships
            $this->createCoreSpecializations();
            
            // Create additional specializations using factory
            $this->createAdditionalSpecializations();
        });
    }

    /**
     * Create core medical specializations with predefined data
     */
    private function createCoreSpecializations(): void
    {
        $coreSpecializations = [
            [
                'name' => 'Cardiology',
                'description' => 'Medical specialty that treats diseases of the heart and blood vessels',
                'photo' => 'https://via.placeholder.com/400x300/E74C3C/FFFFFF?text=Cardiology',
                'service_name' => 'Cardiology',
                'is_active' => true
            ],
            [
                'name' => 'Neurology',
                'description' => 'Medical specialty that treats diseases of the nervous system',
                'photo' => 'https://via.placeholder.com/400x300/9B59B6/FFFFFF?text=Neurology',
                'service_name' => 'Neurology',
                'is_active' => true
            ],
            [
                'name' => 'General Surgery',
                'description' => 'Surgical specialty treating various abdominal and general pathologies',
                'photo' => 'https://via.placeholder.com/400x300/3498DB/FFFFFF?text=Surgery',
                'service_name' => 'General Surgery',
                'is_active' => true
            ],
            [
                'name' => 'Emergency Medicine',
                'description' => 'Medical specialty managing medical emergencies and critical care',
                'photo' => 'https://via.placeholder.com/400x300/E67E22/FFFFFF?text=Emergency',
                'service_name' => 'Emergency Department',
                'is_active' => true
            ],
            [
                'name' => 'Pediatrics',
                'description' => 'Medical specialty dedicated to the care of children and adolescents',
                'photo' => 'https://via.placeholder.com/400x300/F7DC6F/FFFFFF?text=Pediatrics',
                'service_name' => 'Pediatrics',
                'is_active' => true
            ],
            [
                'name' => 'Gynecology-Obstetrics',
                'description' => 'Medical specialty dedicated to female reproductive health and obstetrics',
                'photo' => 'https://via.placeholder.com/400x300/BB8FCE/FFFFFF?text=Gynecology',
                'service_name' => 'Gynecology-Obstetrics',
                'is_active' => true
            ],
            [
                'name' => 'Radiology',
                'description' => 'Medical specialty using medical imaging for diagnosis',
                'photo' => 'https://via.placeholder.com/400x300/85C1E9/FFFFFF?text=Radiology',
                'service_name' => 'Radiology',
                'is_active' => true
            ],
            [
                'name' => 'Laboratory Medicine',
                'description' => 'Medical specialty of biological analysis and laboratory diagnostics',
                'photo' => 'https://via.placeholder.com/400x300/82E0AA/FFFFFF?text=Laboratory',
                'service_name' => 'Laboratory',
                'is_active' => true
            ],
            [
                'name' => 'Hospital Pharmacy',
                'description' => 'Hospital pharmaceutical specialty and medication management',
                'photo' => 'https://via.placeholder.com/400x300/F8C471/FFFFFF?text=Pharmacy',
                'service_name' => 'Pharmacy',
                'is_active' => true
            ]
        ];

        foreach ($coreSpecializations as $spec) {
            // Find or create the service
             $service = Service::firstOrCreate(
                 ['name' => $spec['service_name']],
                 [
                     'description' => 'Service for ' . $spec['service_name'],
                     'image_url' => 'https://via.placeholder.com/400x300/007bff/FFFFFF?text=' . urlencode($spec['service_name']),
                     'start_time' => $spec['service_name'] === 'Emergency Department' ? '00:00' : '08:00',
                     'end_time' => $spec['service_name'] === 'Emergency Department' ? '23:59' : '17:00',
                     'agmentation' => 100.00, // Default augmentation percentage
                     'is_active' => true
                 ]
             );

            // Create the specialization only if it doesn't exist
            Specialization::firstOrCreate(
                ['name' => $spec['name']],
                [
                    'photo' => $spec['photo'],
                    'description' => $spec['description'],
                    'service_id' => $service->id,
                    'is_active' => $spec['is_active']
                ]
            );
        }
    }

    /**
     * Create additional specializations using factory
     */
    private function createAdditionalSpecializations(): void
    {
        // Get existing services or create fallback
        $services = Service::where('is_active', true)->get();
        
        if ($services->isEmpty()) {
            // Create a fallback service if none exist
            $fallbackService = Service::create([
                'name' => 'General Service',
                'description' => 'General service for diverse specializations',
                'image_url' => 'https://via.placeholder.com/400x300/007bff/FFFFFF?text=General+Service',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'agmentation' => 100.00,
                'is_active' => true
            ]);
            $services = collect([$fallbackService]);
        }

        // Create additional specializations using factory, but check for uniqueness
        $existingNames = Specialization::pluck('name')->toArray();
        $additionalCount = 0;
        $maxAttempts = 50; // Prevent infinite loop
        $attempts = 0;

        while ($additionalCount < 15 && $attempts < $maxAttempts) {
            $attempts++;
            
            try {
                $specialization = Specialization::factory()
                    ->state([
                        'service_id' => $services->random()->id
                    ])
                    ->make();

                // Check if name already exists
                if (!in_array($specialization->name, $existingNames)) {
                    $specialization->save();
                    $existingNames[] = $specialization->name;
                    $additionalCount++;
                }
            } catch (\Exception $e) {
                // Skip this iteration if there's an error
                continue;
            }
        }
    }
}