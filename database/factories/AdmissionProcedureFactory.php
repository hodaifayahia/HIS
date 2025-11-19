<?php

namespace Database\Factories;

use App\Models\AdmissionProcedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdmissionProcedure>
 */
class AdmissionProcedureFactory extends Factory
{
    protected $model = AdmissionProcedure::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'admission_id' => \App\Models\Admission::factory(),
            'prestation_id' => \App\Models\Prestation::factory(),
            'name' => $this->faker->word().' Surgery',
            'description' => $this->faker->sentence(),
            'status' => 'scheduled',
            'is_medication_conversion' => false,
            'performed_by' => null,
            'scheduled_at' => $this->faker->dateTimeThisMonth(),
            'created_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Scheduled state
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    /**
     * In progress state
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Completed state
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancelled state
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Medication conversion state
     */
    public function medicationConversion(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Medication Charges',
            'is_medication_conversion' => true,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
