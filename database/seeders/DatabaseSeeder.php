<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder; // Ensure this is imported

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create(); // Uncomment if you want 10 random users

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        
        // Add this line to call your PavilionSeeder
        $this->call([
            PavilionSeeder::class,
        ]);
    }
}