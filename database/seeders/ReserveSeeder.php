<?php

namespace Database\Seeders;

use App\Models\Stock\Reserve;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ReserveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('⚠️  No users found. Skipping reserve seeding.');
            return;
        }

        echo "\n🚀 Creating 25 pharmacy reserve records...\n";

        $reserves = [
            [
                'name' => 'Emergency Medication Reserve',
                'description' => 'Reserved for critical care emergencies',
                'status' => 'active',
            ],
            [
                'name' => 'Daily Operating Stock Reserve',
                'description' => 'Standard daily operating reserve',
                'status' => 'active',
            ],
            [
                'name' => 'Seasonal Flu Vaccination Reserve',
                'description' => 'Reserved for flu season preparation',
                'status' => 'active',
            ],
            [
                'name' => 'Controlled Substance Reserve',
                'description' => 'Reserved for controlled substances',
                'status' => 'active',
            ],
            [
                'name' => 'Pediatric Reserve',
                'description' => 'Reserved for pediatric medications',
                'status' => 'active',
            ],
            [
                'name' => 'Surgical Supply Reserve',
                'description' => 'Reserved for surgical procedures',
                'status' => 'active',
            ],
            [
                'name' => 'Chronic Disease Management Reserve',
                'description' => 'Reserved for chronic disease medications',
                'status' => 'active',
            ],
            [
                'name' => 'Pain Management Reserve',
                'description' => 'Reserved for pain management medications',
                'status' => 'inactive',
            ],
            [
                'name' => 'Antibiotic Reserve',
                'description' => 'Reserved for antibiotic medications',
                'status' => 'active',
            ],
            [
                'name' => 'Cardiac Reserve',
                'description' => 'Reserved for cardiac medications',
                'status' => 'active',
            ],
            [
                'name' => 'Dermatology Reserve',
                'description' => 'Reserved for dermatology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Oncology Reserve',
                'description' => 'Reserved for oncology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Ophthalmology Reserve',
                'description' => 'Reserved for ophthalmology medications',
                'status' => 'active',
            ],
            [
                'name' => 'ENT Reserve',
                'description' => 'Reserved for ENT medications',
                'status' => 'active',
            ],
            [
                'name' => 'Orthopedic Reserve',
                'description' => 'Reserved for orthopedic medications',
                'status' => 'active',
            ],
            [
                'name' => 'Neurology Reserve',
                'description' => 'Reserved for neurology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Psychiatry Reserve',
                'description' => 'Reserved for psychiatric medications',
                'status' => 'active',
            ],
            [
                'name' => 'Diabetic Care Reserve',
                'description' => 'Reserved for diabetic care medications',
                'status' => 'active',
            ],
            [
                'name' => 'Respiratory Reserve',
                'description' => 'Reserved for respiratory medications',
                'status' => 'active',
            ],
            [
                'name' => 'Gastrointestinal Reserve',
                'description' => 'Reserved for GI medications',
                'status' => 'active',
            ],
            [
                'name' => 'Rheumatology Reserve',
                'description' => 'Reserved for rheumatology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Endocrinology Reserve',
                'description' => 'Reserved for endocrinology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Nephrology Reserve',
                'description' => 'Reserved for nephrology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Urology Reserve',
                'description' => 'Reserved for urology medications',
                'status' => 'active',
            ],
            [
                'name' => 'Infectious Disease Reserve',
                'description' => 'Reserved for infectious disease medications',
                'status' => 'active',
            ],
        ];

        foreach ($reserves as $index => $reserveData) {
            Reserve::create([
                'name' => $reserveData['name'],
                'description' => $reserveData['description'],
                'status' => $reserveData['status'],
                'created_by' => $users->random()->id,
            ]);

            if (($index + 1) % 5 == 0) {
                echo "✅ Created {$index} reserves\n";
            }
        }

        echo "✅ Successfully created 25 reserve records!\n\n";
    }
}
