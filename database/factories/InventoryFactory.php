<?php
// database/factories/InventoryFactory.php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Stockage;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        $units = ['pieces', 'boxes', 'bottles', 'vials', 'tubes', 'packets', 'strips'];
        $locations = ['A1-01', 'A1-02', 'B2-15', 'C3-08', 'D4-22', 'E5-11', 'F6-07'];

        return [
            'product_id' => Product::factory(),
            'stockage_id' => Stockage::factory(),
            'quantity' => $this->faker->randomFloat(2, 1, 1000),
            'total_units' => $this->faker->randomFloat(2, 1, 1200),
            'unit' => $this->faker->randomElement($units),
            'batch_number' => $this->faker->regexify('[A-Z]{2}[0-9]{6}'),
            'serial_number' => $this->faker->optional()->regexify('[A-Z]{3}[0-9]{8}'),
            'purchase_price' => $this->faker->randomFloat(2, 5, 500),
            'barcode' => $this->faker->optional()->ean13(),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'location' => $this->faker->randomElement($locations),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->randomFloat(2, 0, 10),
            'total_units' => $this->faker->randomFloat(2, 0, 15),
        ]);
    }
}