<?php
// database/factories/DoctorFactory.php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'specialization_id' => Specialization::factory(),
            'allowed_appointment_today' => $this->faker->boolean(70),
            'number_of_patient' => $this->faker->numberBetween(0, 50),
            'frequency' => $this->faker->randomElement(['Daily', 'Weekly', 'Monthly', 'Custom']),
            'specific_date' => $this->faker->optional()->date(),
            'notes' => $this->faker->optional()->sentence(),
            'patients_based_on_time' => $this->faker->boolean(),
            'time_slot' => $this->faker->numberBetween(15, 60), // Minutes
            'appointment_booking_window' => $this->faker->numberBetween(1, 30), // Days in advance
            'include_time' => $this->faker->numberBetween(0, 1),
            'created_by' => $this->faker->numberBetween(1, 10),
            'user_id' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_appointment_today' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_appointment_today' => false,
        ]);
    }
}