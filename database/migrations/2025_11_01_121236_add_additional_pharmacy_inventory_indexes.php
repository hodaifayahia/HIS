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
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            // Search optimization for commonly searched fields
            $table->index('batch_number', 'idx_pharmacy_inv_batch_number');
            $table->index('serial_number', 'idx_pharmacy_inv_serial_number');
            $table->index('barcode', 'idx_pharmacy_inv_barcode');
            $table->index('location', 'idx_pharmacy_inv_location');
            
            // Timestamp indexes for sorting and filtering
            $table->index('created_at', 'idx_pharmacy_inv_created_at');
            $table->index('updated_at', 'idx_pharmacy_inv_updated_at');
            
            // Composite indexes for complex queries
            $table->index(['pharmacy_stockage_id', 'quantity'], 'idx_pharmacy_inv_stockage_qty');
            $table->index(['expiry_date', 'quantity'], 'idx_pharmacy_inv_expiry_qty');
            $table->index(['pharmacy_product_id', 'pharmacy_stockage_id'], 'idx_pharmacy_inv_product_stockage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropIndex('idx_pharmacy_inv_batch_number');
            $table->dropIndex('idx_pharmacy_inv_serial_number');
            $table->dropIndex('idx_pharmacy_inv_barcode');
            $table->dropIndex('idx_pharmacy_inv_location');
            $table->dropIndex('idx_pharmacy_inv_created_at');
            $table->dropIndex('idx_pharmacy_inv_updated_at');
            $table->dropIndex('idx_pharmacy_inv_stockage_qty');
            $table->dropIndex('idx_pharmacy_inv_expiry_qty');
            $table->dropIndex('idx_pharmacy_inv_product_stockage');
        });
    }
};
