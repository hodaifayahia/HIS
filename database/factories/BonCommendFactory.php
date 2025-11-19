<?php
// database/factories/BonCommendFactory.php

namespace Database\Factories;

use App\Models\BonCommend;
use App\Models\Fournisseur;
use App\Models\ServiceDemendPurchcing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BonCommendFactory extends Factory
{
    protected $model = BonCommend::class;

    public function definition(): array
    {
        $statuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $approvalStatuses = ['pending', 'approved', 'rejected', 'under_review'];

        return [
            'bonCommendCode' => 'BC-' . $this->faker->unique()->numerify('######'),
            'fournisseur_id' => Fournisseur::factory(),
            'service_demand_purchasing_id' => ServiceDemendPurchcing::factory(),
            'order_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'expected_delivery_date' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'department' => $this->faker->randomElement(['Pharmacy', 'Surgery', 'Emergency', 'Laboratory', 'Radiology']),
            'priority' => $this->faker->randomElement($priorities),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
            'status' => $this->faker->randomElement($statuses),
            'approval_status' => $this->faker->randomElement($approvalStatuses),
            'has_approver_modifications' => $this->faker->boolean(20),
            'price' => $this->faker->randomFloat(2, 100, 50000),
            'pdf_content' => null,
            'pdf_generated_at' => null,
            'is_confirmed' => $this->faker->boolean(60),
            'confirmed_at' => $this->faker->optional(60)->dateTimeBetween('-1 month', 'now'),
            'confirmed_by' => $this->faker->optional(60)->numberBetween(1, 100),
            'boncommend_confirmed_at' => $this->faker->optional(60)->dateTimeBetween('-1 month', 'now'),
            'attachments' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approval_status' => 'approved',
            'is_confirmed' => true,
            'confirmed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approval_status' => 'pending',
            'is_confirmed' => false,
        ]);
    }
}