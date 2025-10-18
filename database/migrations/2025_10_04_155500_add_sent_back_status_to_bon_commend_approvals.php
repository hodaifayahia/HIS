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
        // MySQL doesn't support adding enum values directly, so we need to alter the column
        DB::statement("ALTER TABLE bon_commend_approvals MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'sent_back') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'sent_back' from enum values
        DB::statement("ALTER TABLE bon_commend_approvals MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
