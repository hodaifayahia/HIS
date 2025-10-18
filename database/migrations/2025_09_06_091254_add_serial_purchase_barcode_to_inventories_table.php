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
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->after('batch_number');
            $table->decimal('purchase_price', 10, 2)->nullable()->after('serial_number');
            $table->string('barcode')->nullable()->after('purchase_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['serial_number', 'purchase_price', 'barcode']);
        });
    }
};
