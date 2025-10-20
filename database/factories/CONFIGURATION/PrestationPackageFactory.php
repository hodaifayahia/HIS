<?php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\PrestationPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CONFIGURATION\PrestationPackage>
 */
class PrestationPackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PrestationPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'price' => $this->faker->randomFloat(2, 50, 2000),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'created_by' => \App\Models\User::factory(),
            'updated_by' => \App\Models\User::factory(),
        ];
    }

    /**
     * Indicate that the package is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}