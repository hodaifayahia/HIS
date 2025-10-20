<?php
// database/factories/ConventionFactory.php

namespace Database\Factories\B2B;

use App\Models\B2B\Convention;
use App\Models\CRM\Organisme;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConventionFactory extends Factory
{
    protected $model = Convention::class;

    public function definition(): array
    {
        $statuses = ['draft', 'active', 'inactive', 'expired', 'terminated'];

        return [
            'organisme_id' => Organisme::factory(),
            'name' => 'Convention ' . $this->faker->company(),
            'status' => $this->faker->randomElement($statuses),
            'activation_at' => $this->faker->optional(70)->dateTimeBetween('-1 year', '+6 months'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'activation_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'activation_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'activation_at' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
        ]);
    }
}