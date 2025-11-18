<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RefundPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create refund-related permissions
        $permissions = [
            'refund.approve' => 'Can approve refunds',
            'refund.view' => 'Can view refunds',
            'refund.create' => 'Can create refunds',
            'refund.edit' => 'Can edit refunds',
            'refund.delete' => 'Can delete refunds',
            'refund.manage' => 'Can manage refunds',
        ];

        foreach ($permissions as $permissionName => $description) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            echo "Created/found permission: {$permissionName}\n";
        }

        echo "Refund permission seeder completed.\n";
    }
}
