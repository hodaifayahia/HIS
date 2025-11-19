<?php
// database/factories/OrganismeFactory.php

namespace Database\Factories\CRM;

use App\Models\CRM\Organisme;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganismeFactory extends Factory
{
    protected $model = Organisme::class;

    public function definition(): array
    {
        $legalForms = ['SARL', 'SPA', 'EURL', 'SNC', 'Association', 'Fondation'];
        $wilayas = [
            'Alger', 'Oran', 'Constantine', 'Annaba', 'Blida', 'Batna', 'Djelfa',
            'Sétif', 'Sidi Bel Abbès', 'Biskra', 'Tébessa', 'El Oued', 'Skikda'
        ];

        return [
            'name' => $this->faker->company(),
            'legal_form' => $this->faker->randomElement($legalForms),
            'trade_register_number' => $this->faker->unique()->numerify('##########'),
            'tax_id_nif' => $this->faker->unique()->numerify('##############'),
            'statistical_id' => $this->faker->unique()->numerify('############'),
            'article_number' => $this->faker->optional()->numerify('######'),
            'wilaya' => $this->faker->randomElement($wilayas),
            'organism_color' => $this->faker->hexColor(),
            'address' => $this->faker->address(),
            'postal_code' => $this->faker->postcode(),
            'phone' => $this->faker->phoneNumber(),
            'fax' => $this->faker->optional()->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
        ];
    }

    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'organism_color' => $color,
        ]);
    }

    public function inWilaya(string $wilaya): static
    {
        return $this->state(fn (array $attributes) => [
            'wilaya' => $wilaya,
        ]);
    }
}