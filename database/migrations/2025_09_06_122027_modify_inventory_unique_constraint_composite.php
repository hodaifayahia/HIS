<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, temporarily disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('inventories', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['product_id']);
            $table->dropForeign(['stockage_id']);

            // Drop the existing unique constraint
            $table->dropUnique(['product_id', 'stockage_id']);

            // Add new composite unique constraint
            $table->unique(['product_id', 'stockage_id', 'batch_number', 'serial_number', 'expiry_date'], 'inventories_composite_unique');

            // Recreate foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('stockage_id')->references('id')->on('stockages')->onDelete('cascade');
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, temporarily disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('inventories', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['product_id']);
            $table->dropForeign(['stockage_id']);

            // Drop the composite unique constraint
            $table->dropUnique('inventories_composite_unique');

            // Recreate the original unique constraint
            $table->unique(['product_id', 'stockage_id']);

            // Recreate foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('stockage_id')->references('id')->on('stockages')->onDelete('cascade');
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
