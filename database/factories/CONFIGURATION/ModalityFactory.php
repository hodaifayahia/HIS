<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModalityFactory extends Factory
{
    protected $model = Modality::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word() . ' Modality',
            'internal_code' => strtoupper($this->faker->unique()->bothify('MOD###??')),
            'image_path' => $this->faker->optional()->imageUrl(300, 200),
            'modality_type_id' => ModalityType::factory(),
            'dicom_ae_title' => $this->faker->optional()->bothify('AE_####'),
            'port' => $this->faker->optional()->numberBetween(1024, 65535),
            'physical_location_id' => null,
            'operational_status' => $this->faker->randomElement(['operational', 'maintenance', 'out-of-service']),
            'integration_protocol' => $this->faker->randomElement(['DICOM', 'HL7', 'REST', 'None']),
            'connection_configuration' => null,
            'data_retrieval_method' => $this->faker->randomElement(['push', 'pull', 'manual']),
            'ip_address' => $this->faker->optional()->ipv4,
            'consumption_type' => $this->faker->randomElement(['per_hour', 'per_session', 'flat']),
            'consumption_unit' => $this->faker->numberBetween(1, 10),
            'frequency' => $this->faker->randomElement(['Daily', 'Weekly', 'Monthly', 'Custom']),
            'time_slot_duration' => $this->faker->numberBetween(10, 120),
            'slot_type' => $this->faker->randomElement(['minutes', 'days']),
            'booking_window' => $this->faker->numberBetween(1, 365),
            'availability_months' => [],
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
