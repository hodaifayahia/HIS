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
        Schema::create('doctor_emergency_plannings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('service_id');
            $table->integer('month'); // 1-12
            $table->integer('year');
            $table->date('planning_date');
            $table->time('shift_start_time');
            $table->time('shift_end_time');
            $table->enum('shift_type', ['day', 'night', 'emergency']);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');

            // Ensure no duplicate assignments for the same date and time period
            $table->unique(['doctor_id', 'planning_date', 'shift_start_time', 'shift_end_time'], 'unique_doctor_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_emergency_plannings');
    }
};
