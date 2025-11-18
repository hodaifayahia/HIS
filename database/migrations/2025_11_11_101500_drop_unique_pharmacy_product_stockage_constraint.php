<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL: Drops the restrictive unique constraint that prevents batch-level inventory tracking.
     *
     * The old constraint: unique(pharmacy_product_id, pharmacy_stockage_id)
     * - Only allowed ONE record per product per stockage
     * - Prevented tracking different batches/serial numbers/expiry dates separately
     *
     * After this migration:
     * - Multiple batches of the same product can exist in the same stockage
     * - Each batch is identified by: product + stockage + batch_number + serial_number + expiry_date + purchase_price
     * - Application logic handles uniqueness via matching queries in BonEntreeController
     */
    public function up(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            // Drop the restrictive unique constraint
            $table->dropUnique('unique_pharmacy_product_stockage');
        });

        \Log::info('Dropped unique_pharmacy_product_stockage constraint - batch-level tracking now enabled');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            // Re-add the unique constraint if rolling back
            // WARNING: This will fail if there are multiple batches of the same product in the same stockage
            $table->unique(['pharmacy_product_id', 'pharmacy_stockage_id'], 'unique_pharmacy_product_stockage');
        });
    }
};
