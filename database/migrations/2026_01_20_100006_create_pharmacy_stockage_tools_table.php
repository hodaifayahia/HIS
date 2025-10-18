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
        Schema::create('pharmacy_stockage_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_stockage_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'out_of_order'])->default('active');
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('maintenance_schedule')->nullable();
            $table->string('location_code')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('pharmacy_stockage_id')->references('id')->on('pharmacy_stockages')->onDelete('cascade');

            // Indexes for better performance
            $table->index(['pharmacy_stockage_id', 'status']);
            $table->index('serial_number');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stockage_tools');
    }
};
