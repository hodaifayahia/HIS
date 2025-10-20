<?php
// database/factories/SpecializationFactory.php

namespace Database\Factories;

use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecializationFactory extends Factory
{
    protected $model = Specialization::class;

    public function definition(): array
    {
        $specializations = [
            'Cardiologie', 'Neurologie', 'Orthopédie', 'Pédiatrie', 'Gynécologie',
            'Dermatologie', 'Ophtalmologie', 'ORL', 'Psychiatrie', 'Radiologie',
            'Anesthésie', 'Chirurgie générale', 'Médecine interne', 'Pneumologie',
            'Gastro-entérologie', 'Endocrinologie', 'Rhumatologie', 'Urologie'
        ];

        return [
            'name' => $this->faker->randomElement($specializations),
            'photo' => $this->faker->optional()->imageUrl(300, 300, 'medical'),
            'description' => $this->faker->sentence(),
            'service_id' => Service::factory(),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}