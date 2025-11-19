<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class CaissePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create common permissions
        $permissions = [
            'caisse.approve' => 'Can approve caisse transactions',
            'caisse.view' => 'Can view caisse transactions',
            'caisse.create' => 'Can create caisse transactions',
            'caisse.edit' => 'Can edit caisse transactions',
            'caisse.delete' => 'Can delete caisse transactions',
            'users.manage' => 'Can manage users',
            'roles.manage' => 'Can manage roles',
            'permissions.manage' => 'Can manage permissions',
            'appointments.manage' => 'Can manage appointments',
            'patients.manage' => 'Can manage patients',
            'reports.view' => 'Can view reports',
            'settings.manage' => 'Can manage system settings',
        ];

        foreach ($permissions as $permissionName => $description) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            echo "Created/found permission: {$permissionName}\n";
        }

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
