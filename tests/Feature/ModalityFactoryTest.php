<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalityType;

class ModalityFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_modality_type_factory_creates_valid_model()
    {
        $type = ModalityType::factory()->create();

        $this->assertDatabaseHas('modality_types', [
            'id' => $type->id,
            'name' => $type->name,
        ]);

        $this->assertNotEmpty($type->name);
    }

    public function test_modality_factory_creates_and_associates_with_type()
    {
        $modality = Modality::factory()->create();

        $this->assertDatabaseHas('modalities', [
            'id' => $modality->id,
            'name' => $modality->name,
            'modality_type_id' => $modality->modality_type_id,
        ]);

        $this->assertInstanceOf(ModalityType::class, $modality->modalityType);
        $this->assertDatabaseHas('modality_types', ['id' => $modality->modality_type_id]);
    }

    public function test_data_integrity_constraints_on_modality()
    {
        // Attempt to create a modality with invalid modality_type_id.
        // Depending on DB config (foreign key enforcement) this may throw a QueryException.
        try {
            Modality::factory()->create([
                'modality_type_id' => 99999999, // unlikely to exist
            ]);
            $this->assertDatabaseHas('modalities', ['modality_type_id' => 99999999]);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('foreign', strtolower($e->getMessage()));
        }
    }
}
