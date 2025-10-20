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
        $medicationTypes = ['Tablet', 'Capsule', 'Syrup', 'Injectable', 'Ointment', 'Drops'];
        $forms = ['Oral', 'Injectable', 'Topical', 'Ophthalmic', 'Otic'];
        $categories = ['Antibiotic', 'Analgesic', 'Anti-inflammatory', 'Cardiovascular', 'Neurological'];

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement($categories),
            'is_request_approval' => $this->faker->boolean(15), // 15% chance of requesting approval
            'code_interne' => $this->faker->unique()->numberBetween(1000, 9999),
            'code_pch' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{4}'),
            'designation' => $this->faker->words(3, true),
            'type_medicament' => $this->faker->randomElement($medicationTypes),
            'forme' => $this->faker->randomElement($forms),
            'boite_de' => $this->faker->numberBetween(1, 100),
            'quantity_by_box' => $this->faker->boolean(70), // 70% chance of having quantity by box
            'nom_commercial' => $this->faker->company() . ' ' . $this->faker->word(),
            'status' => $this->faker->randomElement(['In Stock', 'Low Stock', 'Out of Stock']),
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