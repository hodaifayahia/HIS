<?php
// database/factories/AllergyFactory.php

namespace Database\Factories;

use App\Models\Allergy;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class AllergyFactory extends Factory
{
    protected $model = Allergy::class;

    public function definition(): array
    {
        $allergies = [
            'Peanuts', 'Shellfish', 'Milk', 'Eggs', 'Fish', 'Soy', 'Tree nuts',
            'Wheat', 'Penicillin', 'Aspirin', 'Latex', 'Pollen', 'Dust mites',
            'Pet dander', 'Mold', 'Insect stings', 'Sulfa drugs', 'Iodine'
        ];

        $severities = ['mild', 'moderate', 'severe', 'life-threatening'];

        return [
            'name' => $this->faker->randomElement($allergies),
            'severity' => $this->faker->randomElement($severities),
            'date' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'note' => $this->faker->optional()->sentence(),
            'patient_id' => Patient::factory(),
        ];
    }

    public function severe(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'severe',
        ]);
    }

    public function mild(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'mild',
        ]);
    }
}