<?php
// database/factories/PrestationFactory.php

namespace Database\Factories\CONFIGURATION;

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrestationFactory extends Factory
{
    protected $model = Prestation::class;

    public function definition(): array
    {
        $prestationTypes = ['consultation', 'intervention', 'examen', 'traitement', 'urgence'];
        $prestationNames = [
            'Consultation générale', 'Échographie', 'Radiographie', 'Prise de sang',
            'ECG', 'IRM', 'Scanner', 'Endoscopie', 'Chirurgie mineure',
            'Vaccination', 'Pansement', 'Injection', 'Perfusion'
        ];

        return [
            'name' => $this->faker->randomElement($prestationNames),
            'internal_code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'billing_code' => $this->faker->unique()->regexify('[0-9]{6}'),
            'description' => $this->faker->sentence(),
            'service_id' => Service::factory(),
            'specialization_id' => Specialization::factory(),
            'type' => $this->faker->randomElement($prestationTypes),
            'public_price' => $this->faker->randomFloat(2, 50, 5000),
            'convenience_prix' => $this->faker->randomFloat(2, 100, 8000),
            'tva_const_prestation' => $this->faker->randomFloat(2, 0, 20),
            'vat_rate' => $this->faker->randomFloat(2, 0, 20),
            'night_tariff' => $this->faker->randomFloat(2, 0, 50),
            'consumables_cost' => $this->faker->randomFloat(2, 0, 500),
            'is_social_security_reimbursable' => $this->faker->boolean(60),
            'reimbursement_conditions' => $this->faker->optional()->sentence(),
            'non_applicable_discount_rules' => $this->faker->optional()->sentence(),
            'fee_distribution_model' => $this->faker->randomElement(['fixed', 'percentage', 'hybrid']),
            'primary_doctor_share' => $this->faker->randomFloat(2, 0, 100),
            'primary_doctor_is_percentage' => $this->faker->boolean(),
            'assistant_doctor_share' => $this->faker->randomFloat(2, 0, 50),
            'assistant_doctor_is_percentage' => $this->faker->boolean(),
            'technician_share' => $this->faker->randomFloat(2, 0, 30),
            'default_payment_type' => $this->faker->randomElement(['pre-pay', 'post-pay', 'versement']),
        ];
    }

    public function consultation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'consultation',
            'public_price' => $this->faker->randomFloat(2, 100, 500),
        ]);
    }

    public function intervention(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'intervention',
            'public_price' => $this->faker->randomFloat(2, 500, 5000),
            'consumables_cost' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    public function reimbursable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_social_security_reimbursable' => true,
            'reimbursement_conditions' => 'Remboursable à 100% par la sécurité sociale',
        ]);
    }
}