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
        // Add indexes to service_demand_purchasings table for query performance
        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            // Index for status filtering
            $table->index('status', 'service_demands_status_index');

            // Index for created_at filtering and sorting
            $table->index('created_at', 'service_demands_created_at_index');

            // Composite index for status + created_at queries
            $table->index(['status', 'created_at'], 'service_demands_status_created_at_index');

            // Index for created_by (user filter)
            $table->index('created_by', 'service_demands_created_by_index');

            // Index for is_pharmacy filter
            $table->index('is_pharmacy', 'service_demands_is_pharmacy_index');
        });

        // Add indexes to service_demand_purchasing_items table
        Schema::table('service_demand_purchasing_items', function (Blueprint $table) {
            // Index for service demand relationship
            $table->index('service_demand_purchasing_id', 'service_demand_items_purchasing_id_index');

            // Index for product lookups
            $table->index('product_id', 'service_demand_items_product_id_index');

            // Index for pharmacy product lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            $table->dropIndex('service_demands_status_index');
            $table->dropIndex('service_demands_created_at_index');
            $table->dropIndex('service_demands_status_created_at_index');
            $table->dropIndex('service_demands_created_by_index');
            $table->dropIndex('service_demands_is_pharmacy_index');
        });

        Schema::table('service_demand_purchasing_items', function (Blueprint $table) {
            $table->dropIndex('service_demand_items_purchasing_id_index');
            $table->dropIndex('service_demand_items_product_id_index');
            $table->dropIndex('service_demand_items_pharmacy_product_id_index');
        });
    }
};
