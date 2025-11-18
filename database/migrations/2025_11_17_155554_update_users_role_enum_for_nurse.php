<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('admin','receptionist','doctor','nurse','SuperAdmin')
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            NOT NULL
            DEFAULT 'receptionist'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('admin','receptionist','doctor','SuperAdmin')
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            NOT NULL
            DEFAULT 'receptionist'
        ");
    }
};
