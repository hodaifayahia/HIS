<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\ModalityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModalityTypeFactory extends Factory
{
    protected $model = ModalityType::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word() . ' Modality',
            'description' => $this->faker->sentence(),
            'image_url' => $this->faker->optional()->imageUrl(640, 480),
        ];
    }
}
