<?php
// database/factories/CategoryFactory.php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $categories = [
            'Medical Equipment', 'Pharmaceuticals', 'Surgical Instruments', 
            'Diagnostic Tools', 'Laboratory Supplies', 'Emergency Equipment',
            'Rehabilitation Equipment', 'Dental Supplies', 'Orthopedic Devices',
            'Cardiology Equipment', 'Radiology Supplies', 'Anesthesia Equipment',
            'Wound Care', 'Infection Control', 'Patient Monitoring'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'description' => $this->faker->sentence(),
        ];
    }
}