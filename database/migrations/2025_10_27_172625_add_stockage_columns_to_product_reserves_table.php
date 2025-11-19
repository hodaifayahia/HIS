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
        // Schema::table('product_reserves', function (Blueprint $table) {
        //     $table->unsignedBigInteger('stockage_id')->nullable()->after('pharmacy_product_id');
        //     $table->unsignedBigInteger('pharmacy_stockage_id')->nullable()->after('stockage_id');
        //     $table->unsignedBigInteger('destination_service_id')->nullable()->after('pharmacy_stockage_id');
            
        //     $table->foreign('stockage_id')->references('id')->on('stockages')->onDelete('set null');
        //     $table->foreign('pharmacy_stockage_id')->references('id')->on('pharmacy_stockages')->onDelete('set null');
        //     $table->foreign('destination_service_id')->references('id')->on('services')->onDelete('set null');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_reserves', function (Blueprint $table) {
            $table->dropForeign(['stockage_id']);
            $table->dropForeign(['pharmacy_stockage_id']);
            $table->dropColumn(['stockage_id', 'pharmacy_stockage_id']);
        });
    }
};
