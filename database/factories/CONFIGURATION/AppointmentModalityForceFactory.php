<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\AppointmentModalityForce;
use App\Models\CONFIGURATION\Modality;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentModalityForceFactory extends Factory
{
    protected $model = AppointmentModalityForce::class;

    public function definition()
    {
        return [
            'modality_id' => Modality::factory(),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
            'number_of_patients' => $this->faker->numberBetween(1,5),
            'user_id' => User::factory(),
            'is_able_to_force' => $this->faker->boolean(50),
        ];
    }
}
