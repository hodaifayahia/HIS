<?php
// database/factories/WaitListFactory.php

namespace Database\Factories;

use App\Models\WaitList;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaitListFactory extends Factory
{
    protected $model = WaitList::class;

    public function definition(): array
    {
        $importanceLevels = ['low', 'medium', 'high', 'urgent'];

        return [
            'doctor_id' => $this->faker->optional()->randomElement([null, Doctor::factory()]),
            'appointmentId' => $this->faker->optional()->numberBetween(1, 1000),
            'patient_id' => Patient::factory(),
            'specialization_id' => Specialization::factory(),
            'is_Daily' => $this->faker->boolean(60),
            'created_by' => User::factory(),
            'importance' => $this->faker->randomElement($importanceLevels),
            'notes' => $this->faker->optional()->sentence(),
            'MoveToEnd' => $this->faker->boolean(20),
        ];
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'importance' => 'urgent',
            'is_Daily' => true,
        ]);
    }

    public function withDoctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_id' => Doctor::factory(),
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_Daily' => true,
        ]);
    }
}