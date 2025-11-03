<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'in_transfer' and 'completed' to the pharmacy_stock_movement_items status enum
        DB::statement("ALTER TABLE pharmacy_stock_movement_items MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'executed', 'in_transfer', 'completed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE pharmacy_stock_movement_items MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'executed') DEFAULT 'pending'");
    }
};
