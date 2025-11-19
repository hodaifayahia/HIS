<?php

namespace Database\Factories\Reception;

use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ficheNavette;
use App\Models\CONFIGURATION\Prestation;
use App\Models\B2B\Convention;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ficheNavetteItemFactory extends Factory
{
    protected $model = ficheNavetteItem::class;

    public function definition()
    {
        return [
            'fiche_navette_id' => \App\Models\Reception\ficheNavette::factory(),
            'prestation_id' => \App\Models\CONFIGURATION\Prestation::factory(),
            'doctor_id' => \App\Models\Doctor::factory(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'base_price' => $this->faker->randomFloat(2, 10, 1000),
            'final_price' => $this->faker->randomFloat(2, 10, 1000),
            'patient_share' => $this->faker->randomFloat(2, 0, 500),
            'organisme_share' => $this->faker->randomFloat(2, 0, 500),
            'primary_clinician_id' => $this->faker->optional()->passthrough(\App\Models\User::factory()),
            'assistant_clinician_id' => $this->faker->optional()->passthrough(\App\Models\User::factory()),
            'technician_id' => $this->faker->optional()->passthrough(\App\Models\User::factory()),
            'modality_id' => $this->faker->optional()->numberBetween(1, 20),
            'convention_id' => $this->faker->optional()->numberBetween(1, 10),
            'patient_id' => \App\Models\Patient::factory(),
            'uploaded_file' => $this->faker->optional()->filePath(),
            'family_authorization' => $this->faker->optional()->randomElement(['spouse', 'children', 'all']),
            'custom_name' => $this->faker->optional()->words(3, true),
            'prise_en_charge_date' => $this->faker->optional()->date(),
            'package_id' => $this->faker->optional()->passthrough(\App\Models\CONFIGURATION\PrestationPackage::factory()),
            'is_nursing_consumption' => $this->faker->boolean(),
            'remise_id' => $this->faker->optional()->numberBetween(1, 10),
            'insured_id' => $this->faker->optional()->numberBetween(1, 100),
            'remaining_amount' => $this->faker->randomFloat(2, 0, 1000),
            'paid_amount' => $this->faker->randomFloat(2, 0, 1000),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'partial']),
            'payment_method' => $this->faker->optional()->randomElement(['cash', 'card', 'transfer']),
        ];
    }

    public function withConvention()
    {
        return $this->state(function (array $attributes) {
            return [
                'convention_id' => Convention::factory(),
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'payment_status' => 'paid',
                'paid_amount' => $attributes['final_price'] ?? 100.00,
                'remaining_amount' => 0.00,
            ];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'paid_amount' => 0.00,
                'remaining_amount' => $attributes['final_price'] ?? 100.00,
            ];
        });
    }

    public function withPackage()
    {
        return $this->state(function (array $attributes) {
            return [
                'package_id' => $this->faker->numberBetween(1, 100),
                'prestation_id' => null, // Packages don't have individual prestations
            ];
        });
    }
}