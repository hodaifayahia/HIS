<?php

namespace Database\Factories\Reception;

use App\Models\Reception\ficheNavette;
use App\Models\Patient;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ficheNavetteFactory extends Factory
{
    protected $model = ficheNavette::class;

    public function definition()
    {
        return [
            'patient_id' => Patient::factory(),
            'companion_id' => null,
            'creator_id' => User::factory(),
            'fiche_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'reference' => 'FN-' . $this->faker->unique()->numberBetween(100000, 999999),
            'total_amount' => $this->faker->randomFloat(2, 0, 1000),
            'is_emergency' => $this->faker->boolean(20), // 20% chance of being emergency
            'emergency_doctor_id' => null,
        ];
    }

    public function emergency()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_emergency' => true,
                'emergency_doctor_id' => Doctor::factory(),
                'status' => 'urgent',
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'total_amount' => $this->faker->randomFloat(2, 100, 2000),
            ];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'total_amount' => 0.00,
            ];
        });
    }
}