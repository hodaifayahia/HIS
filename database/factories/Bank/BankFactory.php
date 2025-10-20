<?php

namespace Database\Factories\Bank;

use App\Models\Bank\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Bank',
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'swift_code' => strtoupper($this->faker->unique()->lexify('????????')),
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'website' => $this->faker->url,
            'logo_url' => null,
            'supported_currencies' => ['USD', 'EUR', 'GBP'],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}