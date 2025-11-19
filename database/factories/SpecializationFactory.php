<?php
// database/factories/SpecializationFactory.php

namespace Database\Factories;

use App\Models\Specialization;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecializationFactory extends Factory
{
    protected $model = Specialization::class;

    public function definition(): array
    {
        $specializations = [
            'Cardiology' => 'Medical specialty that treats diseases of the heart and blood vessels',
            'Neurology' => 'Medical specialty that treats diseases of the nervous system',
            'Orthopedics' => 'Surgical specialty that treats disorders of the musculoskeletal system',
            'Pediatrics' => 'Medical specialty dedicated to the care of children and adolescents',
            'Gynecology-Obstetrics' => 'Medical specialty dedicated to women\'s reproductive health',
            'Dermatology' => 'Medical specialty that treats diseases of the skin',
            'Ophthalmology' => 'Medical specialty that treats diseases of the eyes',
            'ENT (Ear, Nose and Throat)' => 'Specialty that treats diseases of the ear, nose and throat',
            'Psychiatry' => 'Medical specialty that treats mental and behavioral disorders',
            'Radiology' => 'Medical specialty using medical imaging for diagnosis',
            'Anesthesiology-Critical Care' => 'Medical specialty managing anesthesia and intensive care',
            'General Surgery' => 'Surgical specialty treating various abdominal pathologies',
            'Internal Medicine' => 'Medical specialty treating diseases of internal organs',
            'Pulmonology' => 'Medical specialty that treats respiratory diseases',
            'Gastroenterology' => 'Medical specialty that treats digestive diseases',
            'Endocrinology' => 'Medical specialty that treats hormonal disorders',
            'Rheumatology' => 'Medical specialty that treats joint diseases',
            'Urology' => 'Surgical specialty that treats diseases of the urinary system',
            'Hematology' => 'Medical specialty that treats blood diseases',
            'Oncology' => 'Medical specialty that treats cancers',
            'Nephrology' => 'Medical specialty that treats kidney diseases',
            'Infectious Diseases' => 'Medical specialty that treats infectious diseases',
            'Geriatrics' => 'Medical specialty dedicated to the care of elderly people',
            'Emergency Medicine' => 'Medical specialty managing medical emergencies',
            'Cardiovascular Surgery' => 'Surgical specialty of the heart and blood vessels',
            'Orthopedic Surgery' => 'Surgical specialty of the musculoskeletal system',
            'Neurosurgery' => 'Surgical specialty of the nervous system',
            'Plastic Surgery' => 'Reconstructive and aesthetic surgical specialty',
            'Occupational Medicine' => 'Preventive specialty for occupational health',
            'Physical Medicine and Rehabilitation' => 'Specialty of functional rehabilitation'
        ];

        $specializationName = $this->faker->unique()->randomElement(array_keys($specializations));
        $description = $specializations[$specializationName];

        return [
            'name' => $specializationName,
            'photo' => $this->faker->optional(0.8)->imageUrl(400, 300, 'medical', true, $specializationName),
            'description' => $description,
            'service_id' => Service::factory(), // Create a service if not provided
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}