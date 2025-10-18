<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; // <-- Add this line

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = ['admin', 'doctor', 'receptionist', 'SuperAdmin'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}