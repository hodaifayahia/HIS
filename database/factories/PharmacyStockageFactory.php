<?php

namespace Database\Factories;

use App\Models\PharmacyStockage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PharmacyStockageFactory extends Factory
{
    protected $model = PharmacyStockage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word().' Pharmacy Stock',
            'description' => $this->faker->sentence(),
            'location' => $this->faker->address(),
            'status' => 'active',
            'type' => $this->faker->randomElement(['warehouse', 'department', 'clinic']),
        ];
    }

    public function mainWarehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main Warehouse',
            'location' => 'Central Pharmacy',
            'status' => 'active',
            'type' => 'warehouse',
        ]);
    }

    public function departmentStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Department Stock - '.$this->faker->word(),
            'location' => $this->faker->buildingNumber().' '.$this->faker->streetAddress(),
            'type' => 'department',
        ]);
    }
}
