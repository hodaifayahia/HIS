<?php
// database/factories/PavilionFactory.php

namespace Database\Factories\INFRASTRUCTURE;

use App\Models\INFRASTRUCTURE\Pavilion;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class PavilionFactory extends Factory
{
    protected $model = Pavilion::class;

    public function definition(): array
    {
        $pavilionNames = [
            'Pavillon A', 'Pavillon B', 'Pavillon C', 'Pavillon Principal',
            'Aile Nord', 'Aile Sud', 'Aile Est', 'Aile Ouest',
            'Bâtiment Central', 'Annexe Médicale', 'Centre de Soins',
            'Unité Spécialisée', 'Bloc Opératoire', 'Urgences'
        ];

        return [
            'name' => $this->faker->randomElement($pavilionNames),
            'description' => $this->faker->sentence(),
            'servie_id' => Service::factory(), // Note: keeping the typo as it exists in the model
            'image_url' => $this->faker->optional()->imageUrl(600, 400, 'architecture'),
        ];
    }

    public function withService($serviceId): static
    {
        return $this->state(fn (array $attributes) => [
            'servie_id' => $serviceId,
        ]);
    }

    public function mainPavilion(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pavillon Principal',
            'description' => 'Pavillon principal de l\'hôpital',
        ]);
    }

    public function emergencyWing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Aile Urgences',
            'description' => 'Aile dédiée aux services d\'urgence',
        ]);
    }
}