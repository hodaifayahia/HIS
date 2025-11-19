<?php
// database/factories/DoctorFactory.php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        // English medical professional titles and names
        $doctorTitles = ['Dr.', 'Prof.', 'Dr.'];
        $englishFirstNames = [
            'James', 'Robert', 'John', 'Michael', 'David', 'William', 'Richard', 'Charles', 'Joseph', 'Thomas',
            'Christopher', 'Daniel', 'Paul', 'Mark', 'Donald', 'Steven', 'Kenneth', 'Andrew', 'Joshua', 'Kevin',
            'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen',
            'Nancy', 'Lisa', 'Betty', 'Helen', 'Sandra', 'Donna', 'Carol', 'Ruth', 'Sharon', 'Michelle',
            'Laura', 'Kimberly', 'Deborah', 'Dorothy', 'Amy', 'Angela', 'Ashley', 'Brenda', 'Emma', 'Olivia'
        ];
        
        $englishLastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
            'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
            'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
            'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores'
        ];

        $frequencies = ['Daily', 'Weekly', 'Monthly', 'Custom'];
        
        $medicalNotes = [
            'Specialized in emergency consultations',
            'Expert in preventive medicine',
            'Training in tropical medicine',
            'Specialist in chronic pathologies',
            'Experience in hospital medicine',
            'Training in palliative care',
            'Expert in sports medicine',
            'Specialized in geriatric medicine',
            'Training in emergency medicine',
            'Expert in family medicine'
        ];

        $firstName = $this->faker->randomElement($englishFirstNames);
        $lastName = $this->faker->randomElement($englishLastNames);
        $title = $this->faker->randomElement($doctorTitles);
        $fullName = $title . ' ' . $firstName . ' ' . $lastName;

        return [
            'specialization_id' => null, // Will be set by seeder
            'allowed_appointment_today' => $this->faker->boolean(75), // 75% allow same-day appointments
            'frequency' => $this->faker->randomElement($frequencies),
            'specific_date' => $this->faker->optional(0.3)->dateTimeBetween('now', '+6 months')?->format('Y-m-d'),
            'notes' => $this->faker->optional(0.7)->randomElement($medicalNotes),
            'patients_based_on_time' => $this->faker->boolean(80), // Most doctors schedule by time
            'time_slot' => $this->faker->randomElement([15, 20, 30, 45, 60]), // Common appointment durations
            'appointment_booking_window' => $this->faker->numberBetween(7, 60), // 1 week to 2 months advance booking
            'include_time' => $this->faker->boolean(90), // Most include time in appointments
            'add_to_waitlist' => $this->faker->boolean(60), // 60% use waitlists
            'is_active' => $this->faker->boolean(90), // 90% active by default
            'created_by' => 1, // Admin user
            'user_id' => User::factory()->state([
                'name' => $fullName,
                'email' => strtolower(str_replace([' ', '.'], ['', ''], $firstName . '.' . $lastName)) . '@hopital.fr',
                'role' => 'doctor',
                'email_verified_at' => now(),
            ]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'allowed_appointment_today' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'allowed_appointment_today' => false,
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_appointment_today' => true,
            'time_slot' => 15, // Quick emergency slots
            'appointment_booking_window' => 1, // Same day booking
            'add_to_waitlist' => true,
            'notes' => 'Médecin d\'urgence - Disponible pour consultations immédiates',
        ]);
    }

    public function consultant(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_slot' => 45, // Longer consultation slots
            'appointment_booking_window' => 30, // Month advance booking
            'patients_based_on_time' => true,
            'notes' => 'Consultations spécialisées sur rendez-vous',
        ]);
    }

    public function surgeon(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_slot' => 60, // Longer surgical consultation slots
            'appointment_booking_window' => 45, // 6 weeks advance booking
            'allowed_appointment_today' => false, // Surgeons typically don't do same-day
            'notes' => 'Chirurgien - Consultations pré et post-opératoires',
        ]);
    }
}