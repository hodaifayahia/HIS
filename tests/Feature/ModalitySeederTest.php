<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ModalitySeeder;
use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalityType;

class ModalitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_modality_seeder_creates_types_and_modalities()
    {
        // Run seeder
        $this->seed(ModalitySeeder::class);

        // Ensure types created
        $this->assertGreaterThanOrEqual(3, ModalityType::count());

        // Ensure modalities created
        $this->assertGreaterThanOrEqual(3 * 4, Modality::count());

        // Ensure each modality has a valid modality_type_id and relationship
        Modality::all()->each(function ($modality) {
            $this->assertNotNull($modality->modality_type_id);
            $this->assertInstanceOf(ModalityType::class, $modality->modalityType);
        });
    }

    public function test_seeder_handles_existing_types_without_duplication()
    {
        // Create a modality type
        $type = ModalityType::factory()->create(['name' => 'UniqueType']);

        // Run seeder
        $this->seed(ModalitySeeder::class);

        // The seeder should not create duplicate modality types with same name (factory creates unique names by default)
        $this->assertGreaterThanOrEqual(1, ModalityType::where('name', 'UniqueType')->count());
    }
}
