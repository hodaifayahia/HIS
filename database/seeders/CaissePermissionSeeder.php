<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class CaissePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Ensure the permission exists
        $approve = Permission::firstOrCreate(['name' => 'caisse.approve']);
        
        echo "Created/found permission: caisse.approve\n";
        
        // 2) Optional: Assign to selected users (you can modify this as needed)
        // Example: assign to users with specific emails or roles
        $adminUsers = User::where('role', 'admin')->get();
        
        foreach ($adminUsers as $user) {
            if (!$user->hasPermissionTo('caisse.approve')) {
                $user->givePermissionTo($approve);
                echo "Assigned caisse.approve permission to: {$user->name}\n";
            } else {
                echo "User {$user->name} already has caisse.approve permission\n";
            }
        }
        
        echo "Caisse permission seeder completed.\n";
    }
}
