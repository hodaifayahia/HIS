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
        Schema::create('pharmacy_storages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->boolean('temperature_controlled')->default(false);
            $table->string('security_level')->nullable();
            $table->string('location_code')->nullable();
            $table->string('warehouse_type')->nullable();
            $table->boolean('controlled_substance_vault')->default(false);
            $table->boolean('refrigeration_unit')->default(false);
            $table->boolean('humidity_controlled')->default(false);
            $table->boolean('light_protection')->default(false);
            $table->string('access_control_level')->nullable();
            $table->boolean('pharmacist_access_only')->default(false);
            $table->boolean('dea_registration_required')->default(false);
            $table->decimal('temperature_min', 5, 1)->nullable();
            $table->decimal('temperature_max', 5, 1)->nullable();
            $table->decimal('humidity_min', 5, 1)->nullable();
            $table->decimal('humidity_max', 5, 1)->nullable();
            $table->string('monitoring_system')->nullable();
            $table->boolean('backup_power')->default(false);
            $table->boolean('alarm_system')->default(false);
            $table->string('compliance_certification')->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->date('next_inspection_due')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');

            // Indexes for better performance
            $table->index(['status', 'type']);
            $table->index('warehouse_type');
            $table->index('location_code');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_storages');
    }
};
