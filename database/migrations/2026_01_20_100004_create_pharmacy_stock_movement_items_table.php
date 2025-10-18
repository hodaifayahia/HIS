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
        // Schema::create('pharmacy_stock_movement_items', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('pharmacy_movement_id')->nullable();
        //     $table->unsignedBigInteger('product_id')->nullable();
        //     $table->decimal('requested_quantity', 10, 2)->nullable();
        //     $table->decimal('approved_quantity', 10, 2)->nullable();
        //     $table->decimal('executed_quantity', 10, 2)->nullable();
        //     $table->decimal('provided_quantity', 10, 2)->nullable();
        //     $table->text('notes')->nullable();
        //     $table->text('controlled_substance_level')->nullable();
        //     $table->boolean('quantity_by_box')->default(false);
        //     $table->enum('status', ['pending', 'approved', 'rejected', 'executed'])->default('pending');
        //     $table->unsignedBigInteger('approved_by')->nullable();
        //     $table->unsignedBigInteger('rejected_by')->nullable();
        //     $table->timestamp('approved_at')->nullable();
        //     $table->timestamp('rejected_at')->nullable();
        //     $table->text('rejection_reason')->nullable();
        //     $table->text('dosage_instructions')->nullable();
        //     $table->text('administration_route')->nullable();
        //     $table->timestamps();

        //     // Foreign key constraints
        //     // $table->foreign('pharmacy_stock_movement_id', 'psmi_movement_fk')->references('id')->on('pharmacy_stock_movements')->onDelete('cascade');
        //     $table->foreign('pharmacy_product_id', 'psmi_product_fk')->references('id')->on('pharmacy_products')->onDelete('cascade');
        //     $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        //      $table->foreign('pharmacy_movement_id', 'psmi_movement_fk')->references('id')->on('pharmacy_stock_movements')->onDelete('set null');

        //     $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');

        //     // Indexes for better performance
        //     $table->index(['pharmacy_stock_movement_id', 'status'], 'psmi_movement_status_idx');
        //     $table->index(['pharmacy_product_id', 'status'], 'psmi_product_status_idx');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_movement_items');
    }
};
