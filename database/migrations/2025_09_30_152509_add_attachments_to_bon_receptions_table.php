<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            // Attachments field already exists in this table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            // Nothing to rollback - field already existed
        });
    }
};
