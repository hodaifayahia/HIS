<?php

namespace Database\Factories;

use App\Models\AdmissionDischargeTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdmissionDischargeTicket>
 */
class AdmissionDischargeTicketFactory extends Factory
{
    protected $model = AdmissionDischargeTicket::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'admission_id' => \App\Models\Admission::factory(),
            'ticket_number' => 'DT-'.$this->faker->unique()->numberBetween(1000, 9999),
            'authorized_by' => \App\Models\Doctor::factory(),
            'generated_at' => now(),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
