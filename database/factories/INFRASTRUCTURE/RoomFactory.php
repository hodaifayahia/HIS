<?php

namespace Database\Factories\INFRASTRUCTURE;

use App\Models\INFRASTRUCTURE\Room;
use App\Models\INFRASTRUCTURE\RoomType;
use App\Models\INFRASTRUCTURE\Pavilion;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition()
    {
        return [
            'name' => 'Room ' . $this->faker->unique()->bothify('??-###'),
            'room_type_id' => null,
            'status' => $this->faker->randomElement(['available','occupied','maintenance']),
            'location' => $this->faker->city(),
            'room_number' => $this->faker->numberBetween(1,200),
            'pavilion_id' => null,
            'service_id' => \App\Models\CONFIGURATION\Service::factory(),
            'number_of_people' => $this->faker->numberBetween(1,6),
        ];
    }
}
