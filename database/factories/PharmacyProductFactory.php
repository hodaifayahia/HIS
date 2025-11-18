<?php

namespace Database\Factories;

use App\Models\PharmacyProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class PharmacyProductFactory extends Factory
{
    protected $model = PharmacyProduct::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word().' '.$this->faker->numberBetween(100, 1000).'mg',
            'generic_name' => $this->faker->word(),
            'brand_name' => $this->faker->company(),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
            'category' => $this->faker->randomElement(['medication', 'vaccine', 'supplement', 'medical_device']),
            'unit_of_measure' => $this->faker->randomElement(['tablet', 'capsule', 'ml', 'mg', 'g', 'box', 'vial', 'bottle']),
            'quantity_by_box' => $this->faker->numberBetween(10, 100),
            'status' => 'active',
            'is_clinical' => $this->faker->boolean(70),
            'is_request_approval' => false,
            'is_controlled_substance' => false,
            'description' => $this->faker->paragraph(),
            'manufacturer' => $this->faker->company(),
            'supplier' => $this->faker->company(),
        ];
    }

    public function medication(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'medication',
            'is_clinical' => true,
        ]);
    }

    public function controlled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_controlled_substance' => true,
            'controlled_substance_schedule' => $this->faker->numberBetween(1, 5),
            'requires_prescription' => true,
        ]);
    }
}
