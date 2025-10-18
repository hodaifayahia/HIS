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
        Schema::create('service_demand_purchasing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_demand_purchasing_id')->constrained('service_demand_purchasings')->name('fk_sdpi_sdp_id');
            $table->foreignId('product_id')->constrained('products')->name('fk_sdpi_product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_demand_purchasing_items');
    }
};
