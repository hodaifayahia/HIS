<?php
// database/seeders/WaitListSeeder.php

namespace Database\Seeders;

use App\Models\WaitList;
use Illuminate\Database\Seeder;

class WaitListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 120 waitlist entries with various priorities
        WaitList::factory()
            ->count(30)
            ->urgent()
            ->create();

        WaitList::factory()
            ->count(40)
            ->withDoctor()
            ->create();

        WaitList::factory()
            ->count(30)
            ->daily()
            ->create();

        WaitList::factory()
            ->count(20)
            ->create(); // Random state
    }
}