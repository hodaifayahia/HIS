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
        Schema::create('pharmacy_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number')->unique()->nullable();
            $table->unsignedBigInteger('pharmacy_product_id')->nullable();
            $table->unsignedBigInteger('requesting_service_id')->nullable();
            $table->unsignedBigInteger('providing_service_id')->nullable();
            $table->unsignedBigInteger('requesting_user_id')->nullable();
            $table->unsignedBigInteger('approving_user_id')->nullable();
            $table->unsignedBigInteger('executing_user_id')->nullable();
            $table->decimal('requested_quantity', 10, 2)->nullable();
            $table->decimal('approved_quantity', 10, 2)->nullable();
            $table->decimal('executed_quantity', 10, 2)->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'in_transfer', 'completed', 'cancelled'])->default('pending');
            $table->text('request_reason')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('execution_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->date('expected_delivery_date')->nullable();

            // Transfer workflow columns
            $table->timestamp('transfer_initiated_at')->nullable();
            $table->unsignedBigInteger('transfer_initiated_by')->nullable();
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->unsignedBigInteger('delivery_confirmed_by')->nullable();
            $table->enum('delivery_status', ['good', 'manque', 'mixed', 'damaged'])->nullable();
            $table->text('delivery_notes')->nullable();
            $table->decimal('missing_quantity', 10, 2)->nullable();
            $table->boolean('requires_prescription')->default(false);

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('pharmacy_product_id')->references('id')->on('pharmacy_products')->onDelete('set null');
            $table->foreign('requesting_service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('providing_service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('requesting_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approving_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('executing_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            $table->foreign('transfer_initiated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delivery_confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->string('prescription_reference')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable();

            // Indexes for better performance

            $table->index(['status', 'created_at']);
            $table->index(['requesting_service_id', 'status']);
            $table->index(['providing_service_id', 'status']);
            $table->index('movement_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_movements');
    }
};
