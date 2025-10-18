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
        // Add 'overstock' to the return_reason enum to match allowed values used by the app
        DB::statement("ALTER TABLE `bon_retour_items` MODIFY `return_reason` ENUM('defective','expired','damaged','wrong_item','quality_issue','other','overstock') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (without 'overstock')
        DB::statement("ALTER TABLE `bon_retour_items` MODIFY `return_reason` ENUM('defective','expired','damaged','wrong_item','quality_issue','other') NOT NULL");
    }
};
