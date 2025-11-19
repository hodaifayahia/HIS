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
        Schema::create('external_prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_prescription_id')->constrained('external_prescriptions')->onDelete('cascade');
            $table->foreignId('pharmacy_product_id')->constrained('pharmacy_products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->boolean('quantity_by_box')->default(false);
            $table->string('unit')->default('unit');
            $table->decimal('quantity_sended', 10, 2)->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->enum('status', ['draft', 'dispensed', 'cancelled'])->default('draft');
            $table->text('cancel_reason')->nullable();
            $table->foreignId('modified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['external_prescription_id', 'status'], 'idx_ext_presc_status');
            $table->index('pharmacy_product_id', 'idx_pharmacy_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_prescription_items');
    }
};
