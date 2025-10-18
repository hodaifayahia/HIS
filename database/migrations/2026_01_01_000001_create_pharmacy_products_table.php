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
        Schema::create('pharmacy_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->string('sku')->unique()->nullable();
            $table->string('category')->default('medication');
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('supplier')->nullable();
            $table->enum('unit_of_measure', [
                'tablet',
                'capsule',
                'ml',
                'mg',
                'g',
                'kg',
                'piece',
                'box',
                'vial',
                'ampoule',
                'syringe',
                'bottle',
                'tube',
                'patch',
                'other',
            ])->default('tablet');
            $table->decimal('strength', 10, 3)->nullable();
            $table->string('strength_unit')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('route_of_administration')->nullable();
            $table->text('active_ingredients')->nullable();
            $table->text('inactive_ingredients')->nullable();
            // $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_controlled_substance')->default(false);
            $table->string('controlled_substance_schedule')->nullable();
            $table->decimal('storage_temperature_min', 5, 2)->nullable();
            $table->decimal('storage_temperature_max', 5, 2)->nullable();
            $table->decimal('storage_humidity_min', 5, 2)->nullable();
            $table->decimal('storage_humidity_max', 5, 2)->nullable();
            $table->text('storage_conditions')->nullable();
            $table->boolean('requires_cold_chain')->default(false);
            $table->boolean('light_sensitive')->default(false);
            $table->integer('shelf_life_days')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('drug_interactions')->nullable();
            $table->text('warnings')->nullable();
            $table->text('precautions')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('markup_percentage', 5, 2)->nullable();
            $table->string('therapeutic_class')->nullable();
            $table->string('pharmacological_class')->nullable();
            $table->string('atc_code')->nullable();
            $table->string('ndc_number')->nullable();
            $table->string('requires_prescription')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_discontinued')->default(false);
            $table->date('discontinued_date')->nullable();
            $table->text('discontinuation_reason')->nullable();
            $table->json('regulatory_info')->nullable();
            $table->json('quality_control_info')->nullable();
            $table->json('packaging_info')->nullable();
            $table->json('labeling_info')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['category', 'is_active']);
            $table->index(['manufacturer', 'is_active']);
            $table->index(['therapeutic_class', 'is_active']);
            $table->index(['requires_prescription', 'is_active']);
            $table->index(['is_controlled_substance', 'is_active']);
            $table->index(['expiry_date', 'is_active']);
            $table->index(['created_at', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_products');
    }
};
