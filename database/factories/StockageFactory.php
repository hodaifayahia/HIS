<?php
// database/factories/StockageFactory.php

namespace Database\Factories;

use App\Models\Stockage;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockageFactory extends Factory
{
    protected $model = Stockage::class;

    public function definition(): array
    {
        $types = ['pharmacy', 'medical_supplies', 'surgical_instruments', 'laboratory', 'radiology'];
        $statuses = ['active', 'inactive', 'maintenance', 'full'];
        $securityLevels = ['low', 'medium', 'high', 'restricted'];
        $warehouseTypes = ['Central Pharmacy (PC)', 'Service Pharmacy (PS)'];

        return [
            'name' => 'Storage ' . $this->faker->word() . ' ' . $this->faker->numberBetween(1, 100),
            'description' => $this->faker->sentence(),
            'location' => $this->faker->randomElement(['Building A', 'Building B', 'Building C']) . ' - Floor ' . $this->faker->numberBetween(1, 5),
            'capacity' => $this->faker->numberBetween(100, 10000),
            'type' => $this->faker->randomElement($types),
            'status' => $this->faker->randomElement($statuses),
            'service_id' => Service::factory(),
            'temperature_controlled' => $this->faker->boolean(30),
            'security_level' => $this->faker->randomElement($securityLevels),
            'location_code' => $this->faker->regexify('[A-Z]{2}[0-9]{2}-[0-9]{3}'),
            'warehouse_type' => $this->faker->randomElement($warehouseTypes),
        ];
    }

    public function pharmacy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pharmacy',
            'temperature_controlled' => true,
            'security_level' => 'high',
            'warehouse_type' => 'Central Pharmacy (PC)',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}