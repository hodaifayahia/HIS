<?php
// database/seeders/DoctorSeeder.php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create predefined doctors with specific specializations
            $this->createPredefinedDoctors();
            
            // Create additional doctors using factory
            $this->createAdditionalDoctors();
        });
    }

    /**
     * Create predefined doctors with specific data and specializations
     */
    private function createPredefinedDoctors(): void
    {
        $predefinedDoctors = [
            [
                'name' => 'Dr. John Smith',
                'email' => 'john.smith@hospital.com',
                'phone' => '+1 555 234 5678',
                'specialization' => 'Cardiology',
                'time_slot' => 30,
                'notes' => 'Chief of Cardiology Department - Expert in interventional cardiology',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Mary Johnson',
                'email' => 'mary.johnson@hospital.com',
                'phone' => '+1 555 235 5679',
                'specialization' => 'Neurology',
                'time_slot' => 45,
                'notes' => 'Neurology specialist - Specialized consultations',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Peter Williams',
                'email' => 'peter.williams@hospital.com',
                'phone' => '+1 555 236 5680',
                'specialization' => 'General Surgery',
                'time_slot' => 60,
                'notes' => 'General surgeon - Pre and post-operative consultations',
                'type' => 'surgeon'
            ],
            [
                'name' => 'Dr. Sarah Davis',
                'email' => 'sarah.davis@hospital.com',
                'phone' => '+1 555 237 5681',
                'specialization' => 'Emergency Medicine',
                'time_slot' => 15,
                'notes' => 'Emergency physician - Available for immediate consultations',
                'type' => 'emergency'
            ],
            [
                'name' => 'Dr. Michael Brown',
                'email' => 'michael.brown@hospital.com',
                'phone' => '+1 555 238 5682',
                'specialization' => 'Pediatrics',
                'time_slot' => 30,
                'notes' => 'Pediatrician - Specialist in child care',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Catherine Wilson',
                'email' => 'catherine.wilson@hospital.com',
                'phone' => '+1 555 239 5683',
                'specialization' => 'Gynecology-Obstetrics',
                'time_slot' => 45,
                'notes' => 'Gynecologist-obstetrician - Pregnancy monitoring and gynecological consultations',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Francis Miller',
                'email' => 'francis.miller@hospital.com',
                'phone' => '+1 555 240 5684',
                'specialization' => 'Radiology',
                'time_slot' => 20,
                'notes' => 'Radiologist - Medical imaging interpretation',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Natalie Taylor',
                'email' => 'natalie.taylor@hospital.com',
                'phone' => '+1 555 241 5685',
                'specialization' => 'Laboratory Medicine',
                'time_slot' => 30,
                'notes' => 'Medical biologist - Laboratory analysis and biological diagnostics',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Alan Anderson',
                'email' => 'alan.anderson@hospital.com',
                'phone' => '+1 555 242 5686',
                'specialization' => 'Hospital Pharmacy',
                'time_slot' => 30,
                'notes' => 'Hospital pharmacist - Pharmaceutical management and medication counseling',
                'type' => 'consultant'
            ],
            [
                'name' => 'Dr. Isabella Thomas',
                'email' => 'isabella.thomas@hospital.com',
                'phone' => '+1 555 243 5687',
                'specialization' => 'Pulmonology',
                'time_slot' => 40,
                'notes' => 'Pulmonologist - Specialist in respiratory diseases',
                'type' => 'consultant'
            ]
        ];

        foreach ($predefinedDoctors as $doctorData) {
            // Find the specialization
            $specialization = Specialization::where('name', $doctorData['specialization'])->first();
            
            if (!$specialization) {
                // Skip if specialization doesn't exist
                continue;
            }

            // Create or find user
            $user = User::firstOrCreate(
                ['email' => $doctorData['email']],
                [
                    'name' => $doctorData['name'],
                    'email' => $doctorData['email'],
                    'phone' => $doctorData['phone'],
                    'role' => 'doctor',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]
            );

            // Create doctor with specific configuration based on type
            $doctorConfig = $this->getDoctorConfigByType($doctorData['type']);
            
            Doctor::firstOrCreate(
                ['user_id' => $user->id],
                array_merge([
                    'specialization_id' => $specialization->id,
                    'time_slot' => $doctorData['time_slot'],
                    'notes' => $doctorData['notes'],
                    'is_active' => true,
                    'created_by' => 1,
                    'add_to_waitlist' => true,
                    'include_time' => true,
                ], $doctorConfig)
            );
        }
    }

    /**
     * Get doctor configuration based on type
     */
    private function getDoctorConfigByType(string $type): array
    {
        switch ($type) {
            case 'emergency':
                return [
                    'allowed_appointment_today' => true,
                    'appointment_booking_window' => 1,
                    'add_to_waitlist' => true,
                    'patients_based_on_time' => true,
                    'include_time' => true,
                    'frequency' => 'Daily',
                ];
            case 'surgeon':
                return [
                    'allowed_appointment_today' => false,
                    'appointment_booking_window' => 45,
                    'add_to_waitlist' => false,
                    'patients_based_on_time' => true,
                    'include_time' => true,
                    'frequency' => 'Weekly',
                ];
            case 'consultant':
            default:
                return [
                    'allowed_appointment_today' => true,
                    'appointment_booking_window' => 30,
                    'add_to_waitlist' => true,
                    'patients_based_on_time' => true,
                    'include_time' => true,
                    'frequency' => 'Daily',
                ];
        }
    }

    /**
     * Create additional doctors using factory
     */
    private function createAdditionalDoctors(): void
    {
        // Get existing specializations to associate with new doctors
        $specializations = Specialization::where('is_active', true)->get();
        
        if ($specializations->isEmpty()) {
            // If no specializations exist, create doctors without specialization
            Doctor::factory()->count(20)->active()->create();
            Doctor::factory()->count(5)->inactive()->create();
            return;
        }

        // Create active doctors with different types
        $this->createDoctorsByType($specializations, 'active', 15);
        $this->createDoctorsByType($specializations, 'emergency', 5);
        $this->createDoctorsByType($specializations, 'consultant', 10);
        $this->createDoctorsByType($specializations, 'surgeon', 8);
        
        // Create some inactive doctors
        $this->createDoctorsByType($specializations, 'inactive', 7);
    }

    /**
     * Create doctors by type with specialization assignment
     */
    private function createDoctorsByType($specializations, string $type, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $specialization = $specializations->random();
            
            $factory = Doctor::factory();
            
            // Apply the appropriate state based on type
            switch ($type) {
                case 'emergency':
                    $factory = $factory->emergency();
                    break;
                case 'consultant':
                    $factory = $factory->consultant();
                    break;
                case 'surgeon':
                    $factory = $factory->surgeon();
                    break;
                case 'inactive':
                    $factory = $factory->inactive();
                    break;
                case 'active':
                default:
                    $factory = $factory->active();
                    break;
            }
            
            $factory->create([
                'specialization_id' => $specialization->id,
            ]);
        }
    }
}