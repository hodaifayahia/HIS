<?php
// database/factories/ServiceFactory.php

namespace Database\Factories;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $services = [
            'Emergency Department' => ['Emergency services', '00:00', '23:59'],
            'Outpatient Consultations' => ['Outpatient consultations', '08:00', '18:00'],
            'Inpatient Services' => ['Inpatient services', '00:00', '23:59'],
            'General Surgery' => ['General surgical services', '07:00', '20:00'],
            'Radiology' => ['Medical imaging services', '08:00', '17:00'],
            'Laboratory' => ['Laboratory and diagnostic services', '06:00', '22:00'],
            'Pharmacy' => ['Pharmacy services', '08:00', '20:00'],
            'Physiotherapy' => ['Physiotherapy and rehabilitation', '08:00', '17:00'],
            'Cardiology' => ['Cardiovascular services', '08:00', '17:00'],
            'Neurology' => ['Neurological services', '08:00', '17:00'],
            'Pediatrics' => ['Pediatric services', '08:00', '18:00'],
            'Gynecology-Obstetrics' => ['Women\'s health services', '08:00', '18:00'],
            'Orthopedics' => ['Orthopedic services', '08:00', '17:00'],
            'Dermatology' => ['Dermatological services', '08:00', '17:00'],
            'Ophthalmology' => ['Eye care services', '08:00', '17:00'],
            'ENT' => ['Ear, nose and throat services', '08:00', '17:00'],
            'Psychiatry' => ['Mental health services', '08:00', '17:00'],
            'Anesthesia-Intensive Care' => ['Anesthesia and intensive care', '00:00', '23:59'],
            'Internal Medicine' => ['Internal medicine services', '08:00', '17:00'],
            'Oncology' => ['Cancer treatment services', '08:00', '17:00'],
            'Hematology' => ['Blood disorder services', '08:00', '17:00'],
            'Endocrinology' => ['Endocrine system services', '08:00', '17:00'],
            'Nephrology' => ['Kidney disease services', '08:00', '17:00'],
            'Gastroenterology' => ['Digestive system services', '08:00', '17:00'],
            'Pulmonology' => ['Respiratory system services', '08:00', '17:00'],
            'Rheumatology' => ['Joint and connective tissue services', '08:00', '17:00'],
            'Infectious Diseases' => ['Infectious disease services', '08:00', '17:00'],
            'Nuclear Medicine' => ['Nuclear medicine services', '08:00', '17:00'],
            'Pathology' => ['Pathological analysis services', '08:00', '17:00'],
            'Geriatrics' => ['Elderly care services', '08:00', '17:00'],
            'Palliative Care' => ['End-of-life care services', '00:00', '23:59'],
            'Occupational Medicine' => ['Occupational health services', '08:00', '17:00'],
            'Physical Medicine and Rehabilitation' => ['Rehabilitation services', '08:00', '17:00']
        ];

        $serviceData = $this->faker->randomElement(array_keys($services));
        $serviceInfo = $services[$serviceData];

        return [
            'name' => $serviceData,
            'description' => $serviceInfo[0],
            'image_url' => $this->faker->optional(0.7)->imageUrl(400, 300, 'medical'),
            'start_time' => $serviceInfo[1],
            'end_time' => $serviceInfo[2],
            'agmentation' => $this->faker->randomFloat(2, 50, 200), // Augmentation percentage between 50% and 200%
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

    public function emergencyService(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgences',
            'description' => 'Emergency services available 24/7',
            'start_time' => '00:00',
            'end_time' => '23:59',
            'is_active' => true,
        ]);
    }

    public function consultationService(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Consultation externe',
            'description' => 'Outpatient consultation services',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'is_active' => true,
        ]);
    }

    public function surgicalService(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Chirurgie générale',
            'description' => 'General surgical services',
            'start_time' => '07:00',
            'end_time' => '20:00',
            'is_active' => true,
        ]);
    }
}