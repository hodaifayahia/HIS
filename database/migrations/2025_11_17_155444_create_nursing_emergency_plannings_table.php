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
        Schema::create('nursing_emergency_plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('planning_date');
            $table->time('shift_start_time');
            $table->time('shift_end_time');
            $table->enum('shift_type', ['day', 'night', 'emergency']);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['nurse_id', 'planning_date', 'shift_start_time', 'shift_end_time'], 'unique_nurse_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nursing_emergency_plannings');
    }
};
