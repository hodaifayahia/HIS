<?php
// database/factories/ServiceFactory.php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $services = [
            'Urgences', 'Consultation externe', 'Hospitalisation', 'Chirurgie',
            'Radiologie', 'Laboratoire', 'Pharmacie', 'Kinésithérapie',
            'Maternité', 'Pédiatrie', 'Cardiologie', 'Neurologie'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($services),
            'description' => $this->faker->sentence(),
            'image_url' => $this->faker->imageUrl(400, 300, 'medical'),
            'start_time' => $this->faker->time('H:i', '08:00'),
            'end_time' => $this->faker->time('H:i', '18:00'),
            'agmentation' => $this->faker->randomFloat(2, 0, 100),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
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

    public function emergencyService(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgences',
            'start_time' => '00:00',
            'end_time' => '23:59',
            'is_active' => true,
        ]);
    }
}