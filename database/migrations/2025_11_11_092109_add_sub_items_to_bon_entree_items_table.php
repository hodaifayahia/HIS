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
        Schema::table('bon_entree_items', function (Blueprint $table) {
            // Add JSON column to store batch-level sub-items
            // Each sub_item contains: quantity, purchase_price, unit, batch_number, expiry_date, serial_number
            $table->json('sub_items')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_entree_items', function (Blueprint $table) {
            $table->dropColumn('sub_items');
        });
    }
};
