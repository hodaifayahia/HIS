<?php

namespace Database\Factories;

use App\Models\AdmissionDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdmissionDocument>
 */
class AdmissionDocumentFactory extends Factory
{
    protected $model = AdmissionDocument::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'admission_id' => \App\Models\Admission::factory(),
            'type' => $this->faker->randomElement(['consent_form', 'medical_history', 'insurance_card', 'discharge_summary']),
            'is_physical_uploaded' => $this->faker->boolean(),
            'is_digital_verified' => false,
            'file_path' => null,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Uploaded state
     */
    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_physical_uploaded' => true,
            'file_path' => 'admissions/'.$this->faker->uuid().'.pdf',
        ]);
    }

    /**
     * Verified state
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_digital_verified' => true,
        ]);
    }
}
