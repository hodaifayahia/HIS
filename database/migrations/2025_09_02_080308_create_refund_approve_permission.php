<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the refund.approve permission using Spatie Permission
        Permission::firstOrCreate(['name' => 'refund.approve']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the refund.approve permission
        $permission = Permission::where('name', 'refund.approve')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
