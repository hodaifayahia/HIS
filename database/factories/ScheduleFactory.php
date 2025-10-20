<?php
// database/factories/ScheduleFactory.php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $shiftPeriods = ['morning', 'afternoon', 'evening', 'night', 'full_day'];

        return [
            'doctor_id' => Doctor::factory(),
            'day_of_week' => $this->faker->randomElement($daysOfWeek),
            'date' => $this->faker->optional()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'number_of_patients_per_day' => $this->faker->numberBetween(5, 50),
            'start_time' => $this->faker->time('H:i', '08:00'),
            'end_time' => $this->faker->time('H:i', '18:00'),
            'shift_period' => $this->faker->randomElement($shiftPeriods),
            'is_active' => $this->faker->boolean(85),
            'created_by' => User::factory(),
            'updated_by' => $this->faker->optional()->numberBetween(1, 100),
            'deleted_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function morningShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_period' => 'morning',
            'start_time' => '08:00',
            'end_time' => '14:00',
        ]);
    }

    public function afternoonShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_period' => 'afternoon',
            'start_time' => '14:00',
            'end_time' => '20:00',
        ]);
    }
}