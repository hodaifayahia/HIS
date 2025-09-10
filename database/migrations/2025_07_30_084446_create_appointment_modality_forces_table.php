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
       
        Schema::create('appointment_modality_forces', function (Blueprint $table) {
            $table->id();
            // Foreign key to modalities table
            
            $table->foreignId('modality_id')->constrained('modalities')->onDelete('cascade');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('number_of_patients')->default(1)->comment('Number of patients that can be forced into this appointment');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_able_to_force')->default(false)->comment('Indicates if the user can force appointments for this modality');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_modality_forces');
    }
};
