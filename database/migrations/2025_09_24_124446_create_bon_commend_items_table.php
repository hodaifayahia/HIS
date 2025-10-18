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
        Schema::create('bon_commend_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factureproforma_id')->constrained('factureproformas')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->string('quntity_by_box')->nullable();
            $table->string('quantity_desired')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_commend_items');
    }
};
