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
        Schema::create('pharmacy_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_product_id');
            $table->unsignedBigInteger('pharmacy_stockage_id');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('location')->nullable();
            $table->string('barcode')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('pharmacy_product_id')->references('id')->on('pharmacy_products')->onDelete('cascade');
            $table->foreign('pharmacy_stockage_id')->references('id')->on('pharmacy_stockages')->onDelete('cascade');

            // Unique constraint
            $table->unique(['pharmacy_product_id', 'pharmacy_stockage_id'], 'unique_pharmacy_product_stockage');

            // Indexes for better performance
            $table->index(['expiry_date', 'quantity']);
            $table->index('batch_number');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventories');
    }
};
