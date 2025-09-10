<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\INFRASTRUCTURE\Pavilion; // Import the Pavilion model

class PavilionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data (optional, for fresh seeding)
        // Pavilion::truncate();

        // Sample Pavilions based on your document
        $pavilions = [
            [
                'name' => 'Pavillon de Chirurgie',
                'description' => 'Main wing for surgical procedures and post-operative care.',
                // 'display_color' => '#FF5733', // Add if you uncommented in migration
                // 'layout_data' => json_encode(['coords' => '0,0,100,100']), // Add if you uncommented
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pavillon Mère-Enfant',
                'description' => 'Dedicated wing for maternity, pediatrics, and child care.',
                // 'display_color' => '#33FF57',
                // 'layout_data' => json_encode(['coords' => '100,0,200,100']),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'name' => 'Pavillon des Consultations Externes',
                'description' => 'Outpatient department for various medical consultations.',
                // 'display_color' => '#3357FF',
                // 'layout_data' => json_encode(['coords' => '0,100,100,200']),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'name' => 'Pavillon de Médecine Interne',
                'description' => 'Wing for general internal medicine and chronic disease management.',
                // 'display_color' => '#FFCC33',
                // 'layout_data' => json_encode(['coords' => '100,100,200,200']),
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'Plateau Technique',
                'description' => 'Technical platform housing imaging, laboratory, and specialized diagnostic services.',
                // 'display_color' => '#A033FF',
                // 'layout_data' => json_encode(['coords' => '200,0,300,100']),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
        ];

        foreach ($pavilions as $pavilionData) {
            Pavilion::create($pavilionData);
        }
    }
}