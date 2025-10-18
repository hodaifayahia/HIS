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
        Schema::create('pharmacy_stockages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->integer('service_id')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->boolean('temperature_controlled')->default(false);
            $table->string('security_level')->nullable();
            $table->string('location_code')->nullable();
            $table->enum('warehouse_type', ['Central Pharmacy (PC)', 'Service Pharmacy (PS)'])->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');

            // Indexes for better performance
            $table->index(['status', 'type']);
            $table->index('warehouse_type');
            $table->index('location_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stockages');
    }
};
