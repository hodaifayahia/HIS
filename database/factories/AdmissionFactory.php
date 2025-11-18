<?php

namespace Database\Factories;

use App\Models\Admission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admission>
 */
class AdmissionFactory extends Factory
{
    protected $model = Admission::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'patient_id' => \App\Models\Patient::factory(),
            'doctor_id' => \App\Models\Doctor::factory(),
            'type' => $this->faker->randomElement(['surgery', 'nursing']),
            'status' => 'admitted',
            'admitted_at' => now(),
            'discharged_at' => null,
            'documents_verified' => false,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Surgery admission state
     */
    public function surgery(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'surgery',
            'initial_prestation_id' => \App\Models\Prestation::factory(),
        ]);
    }

    /**
     * Nursing admission state
     */
    public function nursing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'nursing',
        ]);
    }

    /**
     * In service state
     */
    public function inService(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_service',
        ]);
    }

    /**
     * Document pending state
     */
    public function documentPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'document_pending',
        ]);
    }

    /**
     * Discharged state
     */
    public function discharged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready_for_discharge',
            'discharged_at' => now(),
            'documents_verified' => true,
        ]);
    }
}
