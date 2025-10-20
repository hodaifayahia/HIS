<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Credentials to create
        $email = 'admin@example.com';
        $password = 'Admin123!';

        // Create or get existing user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'phone' => '0000000000',
                'is_active' => true,
                'role' => 'admin',
                'created_by' => 1,
            ]
        );

        // Ensure the admin role exists, then assign it
        try {
            Role::firstOrCreate(['name' => 'admin']);
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('admin');
            }
        } catch (\Throwable $e) {
            // If Role model isn't available or package not configured, skip silently
            $this->command->warn('Could not create/assign admin role: ' . $e->getMessage());
        }

        // Output to the console when running seeder
        $this->command->info("Admin user ensured: {$email} (password: {$password})");
    }
}
