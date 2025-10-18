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
        Schema::create('pharmacy_product_global_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_product_id');
            $table->integer('global_min_threshold')->nullable();
            $table->integer('global_max_threshold')->nullable();
            $table->string('default_unit')->nullable();
            $table->decimal('standard_cost', 10, 2)->nullable();
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->boolean('is_controlled_substance')->default(false);
            $table->boolean('requires_prescription')->default(false);
            $table->json('storage_requirements')->nullable();
            $table->json('handling_instructions')->nullable();
            $table->json('disposal_instructions')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('pharmacy_product_id')->references('id')->on('pharmacy_products')->onDelete('cascade');

            // Unique constraint
            $table->unique('pharmacy_product_id');

            // Indexes for better performance
            $table->index(['is_controlled_substance', 'requires_prescription'], 'ppgs_controlled_rx_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_product_global_settings');
    }
};
