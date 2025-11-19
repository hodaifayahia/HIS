<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\ModalityAvailableMonth;
use App\Models\CONFIGURATION\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModalityAvailableMonthFactory extends Factory
{
    protected $model = ModalityAvailableMonth::class;

    public function definition()
    {
        return [
            'modality_id' => Modality::factory(),
            'month' => $this->faker->numberBetween(1,12),
            'year' => $this->faker->numberBetween(2024,2026),
            'is_available' => $this->faker->boolean(80),
        ];
    }
}
