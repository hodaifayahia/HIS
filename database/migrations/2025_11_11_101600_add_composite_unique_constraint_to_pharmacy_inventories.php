<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a composite unique constraint to pharmacy_inventories that allows batch-level tracking
     * while preventing true duplicates.
     *
     * Unique combination: product + stockage + batch + serial + expiry + purchase_price
     * - Allows different batches of the same product in the same stockage
     * - Prevents duplicate entries for the exact same batch details
     * - Aligns with the matching logic in BonEntreeController
     */
    public function up(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            // Add composite unique constraint for batch-level uniqueness
            // This allows multiple batches but prevents true duplicates
            $table->unique(
                [
                    'pharmacy_product_id',
                    'pharmacy_stockage_id',
                    'batch_number',
                    'serial_number',
                    'expiry_date',
                    'purchase_price',
                ],
                'unique_pharmacy_batch_details'
            );
        });

        \Log::info('Added composite unique constraint for pharmacy inventories - batch-level uniqueness enforced');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropUnique('unique_pharmacy_batch_details');
        });
    }
};
