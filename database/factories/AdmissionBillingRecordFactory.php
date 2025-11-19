<?php

namespace Database\Factories;

use App\Models\AdmissionBillingRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdmissionBillingRecord>
 */
class AdmissionBillingRecordFactory extends Factory
{
    protected $model = AdmissionBillingRecord::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'admission_id' => \App\Models\Admission::factory(),
            'procedure_id' => null,
            'item_type' => $this->faker->randomElement(['procedure', 'service', 'nursing_care']),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'is_paid' => false,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Paid state
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
        ]);
    }

    /**
     * Procedure item
     */
    public function procedure(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'procedure',
            'procedure_id' => \App\Models\AdmissionProcedure::factory(),
        ]);
    }

    /**
     * Service item
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'service',
        ]);
    }

    /**
     * Nursing care item
     */
    public function nursingCare(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'nursing_care',
        ]);
    }
}
