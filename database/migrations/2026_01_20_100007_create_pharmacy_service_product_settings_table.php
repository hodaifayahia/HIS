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
        Schema::create('pharmacy_service_product_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('pharmacy_product_id');
            $table->integer('low_stock_threshold')->nullable();
            $table->integer('critical_stock_threshold')->nullable();
            $table->integer('max_stock_level')->nullable();
            $table->integer('reorder_point')->nullable();
            $table->string('preferred_supplier')->nullable();
            $table->string('custom_name')->nullable();
            $table->integer('priority_level')->default(1);
            $table->string('alert_frequency')->nullable();
            $table->boolean('auto_reorder')->default(false);
            $table->text('notes')->nullable();
            $table->string('color_code')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('pharmacy_product_id')->references('id')->on('pharmacy_products')->onDelete('cascade');

            // Unique constraint
            $table->unique(['service_id', 'pharmacy_product_id'], 'unique_pharmacy_service_product');

            // Indexes for better performance
            $table->index(['service_id', 'priority_level'], 'psps_service_priority_idx');
            $table->index(['pharmacy_product_id', 'auto_reorder'], 'psps_product_reorder_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_service_product_settings');
    }
};
