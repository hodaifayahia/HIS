<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds pharmacy_product_id to bon_entree_items to support both:
     * - Regular products (product_id) → stored in inventories table
     * - Pharmacy products (pharmacy_product_id) → stored in pharmacy_inventories table
     *
     * CRITICAL: One and only one of product_id or pharmacy_product_id should be set per item
     */
    public function up(): void
    {
        Schema::table('bon_entree_items', function (Blueprint $table) {
            // Add pharmacy_product_id as nullable foreign key
            $table->unsignedBigInteger('pharmacy_product_id')->nullable()->after('product_id');

            // Add foreign key constraint
            $table->foreign('pharmacy_product_id')
                ->references('id')
                ->on('pharmacy_products')
                ->onDelete('cascade');

            // Make product_id nullable since items can be either regular OR pharmacy products
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });

        \Log::info('Added pharmacy_product_id to bon_entree_items - now supports both product types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_entree_items', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_product_id']);
            $table->dropColumn('pharmacy_product_id');

            // Make product_id required again
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
