<?php
// database/factories/MedicationFactory.php

namespace Database\Factories;

use App\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        $medicationTypes = ['Antibiotique', 'Antalgique', 'Anti-inflammatoire', 'Cardiovasculaire', 'Neurologique', 'Digestif'];
        $formes = ['Comprimé', 'Gélule', 'Sirop', 'Injectable', 'Pommade', 'Gouttes', 'Suppositoire'];
        $designations = [
            'Paracétamol', 'Ibuprofène', 'Amoxicilline', 'Aspirine', 'Oméprazole',
            'Métformine', 'Atorvastatine', 'Lisinopril', 'Amlodipine', 'Simvastatine'
        ];

        return [
            'designation' => $this->faker->randomElement($designations) . ' ' . $this->faker->numberBetween(10, 500) . 'mg',
            'nom_commercial' => $this->faker->company() . ' ' . $this->faker->word(),
            'type_medicament' => $this->faker->randomElement($medicationTypes),
            'forme' => $this->faker->randomElement($formes),
            'boite_de' => $this->faker->numberBetween(10, 100),
            'code_pch' => $this->faker->regexify('[A-Z]{2}[0-9]{6}'),
        ];
    }

    public function antibiotic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_medicament' => 'Antibiotique',
            'designation' => 'Amoxicilline ' . $this->faker->numberBetween(250, 1000) . 'mg',
        ]);
    }

    public function painkiller(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_medicament' => 'Antalgique',
            'designation' => 'Paracétamol ' . $this->faker->numberBetween(500, 1000) . 'mg',
        ]);
    }
}