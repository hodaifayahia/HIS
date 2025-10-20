<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\ModalitySchedule;
use App\Models\CONFIGURATION\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModalityScheduleFactory extends Factory
{
    protected $model = ModalitySchedule::class;

    public function definition()
    {
        return [
            'modality_id' => Modality::factory(),
            'day_of_week' => $this->faker->randomElement(['monday','tuesday','wednesday','thursday','friday','saturday','sunday']),
            'shift_period' => $this->faker->randomElement(['morning','afternoon']),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'slot_type' => $this->faker->randomElement(['minutes','days']),
            'is_active' => $this->faker->boolean(90),
            'time_slot_duration' => $this->faker->numberBetween(10,120),
            'break_duration' => $this->faker->numberBetween(0,60),
            'break_times' => [],
            'excluded_dates' => [],
            'modified_times' => [],
        ];
    }
}
