<?php
// database/factories/ProductFactory.php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $medicamentTypes = ['Comprimé', 'Gélule', 'Sirop', 'Injectable', 'Pommade', 'Gouttes'];
        $formes = ['Oral', 'Injectable', 'Topique', 'Ophtalmique', 'Auriculaire'];
        $categories = ['Antibiotique', 'Antalgique', 'Anti-inflammatoire', 'Cardiovasculaire', 'Neurologique'];

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement($categories),
            'is_clinical' => $this->faker->boolean(30), // 30% chance of being clinical
            'is_required_approval' => $this->faker->boolean(20), // 20% chance of requiring approval
            'is_request_approval' => $this->faker->boolean(15), // 15% chance of requesting approval
            'code_interne' => $this->faker->unique()->numberBetween(1000, 9999),
            'code_pch' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{4}'),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'designation' => $this->faker->words(3, true),
            'type_medicament' => $this->faker->randomElement($medicamentTypes),
            'forme' => $this->faker->randomElement($formes),
            'boite_de' => $this->faker->numberBetween(1, 100),
            'nom_commercial' => $this->faker->company() . ' ' . $this->faker->word(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'discontinued']),
        ];
    }

    public function clinical(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_clinical' => true,
        ]);
    }

    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required_approval' => true,
            'is_request_approval' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}