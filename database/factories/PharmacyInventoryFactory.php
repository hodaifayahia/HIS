<?php

namespace Database\Factories;

use App\Models\PharmacyInventory;
use App\Models\PharmacyProduct;
use App\Models\PharmacyStockage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PharmacyInventoryFactory extends Factory
{
    protected $model = PharmacyInventory::class;

    public function definition(): array
    {
        return [
            'pharmacy_product_id' => PharmacyProduct::factory(),
            'pharmacy_stockage_id' => PharmacyStockage::factory(),
            'quantity' => $this->faker->numberBetween(10, 1000),
            'unit' => $this->faker->randomElement(['box', 'unit', 'blister', 'bottle']),
            'purchase_price' => $this->faker->randomFloat(2, 1, 100),
            'selling_price' => $this->faker->randomFloat(2, 5, 200),
            'batch_number' => $this->faker->regexify('[A-Z]{2}[0-9]{6}'),
            'expiry_date' => $this->faker->dateTimeBetween('+1 month', '+2 years'),
            'purchase_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
