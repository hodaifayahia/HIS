<?php

namespace Database\Factories\B2B;

use App\Models\B2B\ConventionDetail;
use App\Models\B2B\Convention;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConventionDetailFactory extends Factory
{
    protected $model = ConventionDetail::class;

    public function definition(): array
    {
        return [
            'convention_id' => Convention::factory(),
            'head' => $this->faker->boolean(80),
            'updated_by_id' => 1, // Default to user ID 1 since it's required
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'family_auth' => json_encode($this->faker->randomElement([
                ['spouse'],
                ['children'],
                ['spouse', 'children'],
                ['all']
            ])),
            'max_price' => $this->faker->randomFloat(2, 1000, 50000),
            'min_price' => $this->faker->randomFloat(2, 100, 1000),
            'discount_percentage' => $this->faker->randomFloat(2, 5, 50),
            'avenant_id' => null,
            'extension_count' => $this->faker->numberBetween(0, 3),
        ];
    }

    public function withAvenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'avenant_id' => $this->faker->numberBetween(1, 100),
            'head' => false,
        ]);
    }

    public function head(): static
    {
        return $this->state(fn (array $attributes) => [
            'head' => true,
            'avenant_id' => null,
        ]);
    }

    public function highDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $this->faker->randomFloat(2, 30, 70),
        ]);
    }
}