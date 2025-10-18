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
        Schema::table('service_demand_purchasing_items', function (Blueprint $table) {
            // Add foreign key constraints with custom names
            $table->foreign('service_demand_purchasing_id', 'fk_sdpi_sdp_id')
                ->references('id')->on('service_demand_purchasings')
                ->onDelete('cascade');

            // Check if products table exists before adding foreign key
            if (Schema::hasTable('products')) {
                $table->foreign('product_id', 'fk_sdpi_product_id')
                    ->references('id')->on('products')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_demand_purchasing_items', function (Blueprint $table) {
            $table->dropForeign('fk_sdpi_sdp_id');
            if (Schema::hasTable('products')) {
                $table->dropForeign('fk_sdpi_product_id');
            }
        });
    }
};
