<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use App\Models\FournisseurContact;
use Illuminate\Database\Eloquent\Factories\Factory;

class FournisseurContactFactory extends Factory
{
    protected $model = FournisseurContact::class;

    public function definition(): array
    {
        return [
            'fournisseur_id' => Fournisseur::factory(),
            'name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'is_primary' => $this->faker->boolean(30), // 30% chance of being primary
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => false,
        ]);
    }
}
