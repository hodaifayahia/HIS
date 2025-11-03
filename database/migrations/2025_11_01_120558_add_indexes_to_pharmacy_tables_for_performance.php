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
        // Add indexes to pharmacy_products table
        Schema::table('pharmacy_products', function (Blueprint $table) {
            // Search optimization
            $table->index('name', 'idx_pharmacy_products_name');
            $table->index('code', 'idx_pharmacy_products_code');
            
            // Filter optimization
            $table->index('category', 'idx_pharmacy_products_category');
            $table->index('medication_type', 'idx_pharmacy_products_medication_type');
            $table->index('is_active', 'idx_pharmacy_products_is_active');
            $table->index('is_controlled_substance', 'idx_pharmacy_products_controlled');
            $table->index('requires_prescription', 'idx_pharmacy_products_prescription');
            
            // Sorting optimization
            $table->index('created_at', 'idx_pharmacy_products_created_at');
            
            // Composite indexes for common queries
            $table->index(['category', 'is_active'], 'idx_pharmacy_products_category_active');
            $table->index(['created_at', 'is_active'], 'idx_pharmacy_products_created_active');
        });

        // Add indexes to pharmacy_inventories table
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            // Foreign key optimization
            $table->index('pharmacy_product_id', 'idx_pharmacy_inventories_product_id');
            $table->index('pharmacy_stockage_id', 'idx_pharmacy_inventories_stockage_id');
            
            // Filter and calculation optimization
            $table->index('quantity', 'idx_pharmacy_inventories_quantity');
            $table->index('expiry_date', 'idx_pharmacy_inventories_expiry_date');
            
            // Composite indexes for common queries
            $table->index(['pharmacy_product_id', 'quantity'], 'idx_pharmacy_inventories_product_qty');
            $table->index(['pharmacy_product_id', 'expiry_date'], 'idx_pharmacy_inventories_product_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from pharmacy_products table
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropIndex('idx_pharmacy_products_name');
            $table->dropIndex('idx_pharmacy_products_code');
            $table->dropIndex('idx_pharmacy_products_category');
            $table->dropIndex('idx_pharmacy_products_medication_type');
            $table->dropIndex('idx_pharmacy_products_is_active');
            $table->dropIndex('idx_pharmacy_products_controlled');
            $table->dropIndex('idx_pharmacy_products_prescription');
            $table->dropIndex('idx_pharmacy_products_created_at');
            $table->dropIndex('idx_pharmacy_products_category_active');
            $table->dropIndex('idx_pharmacy_products_created_active');
        });

        // Drop indexes from pharmacy_inventories table
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropIndex('idx_pharmacy_inventories_product_id');
            $table->dropIndex('idx_pharmacy_inventories_stockage_id');
            $table->dropIndex('idx_pharmacy_inventories_quantity');
            $table->dropIndex('idx_pharmacy_inventories_expiry_date');
            $table->dropIndex('idx_pharmacy_inventories_product_qty');
            $table->dropIndex('idx_pharmacy_inventories_product_expiry');
        });
    }
};
