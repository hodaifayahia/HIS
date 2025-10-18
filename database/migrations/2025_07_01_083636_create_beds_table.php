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
         Schema::create('beds', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade'); // Not null foreign key
            $table->string('bed_identifier', 191)->comment('e.g., A, B'); // Not null
            $table->string('status')->default('free')->comment('free, occupied, reserved'); // Not null with default
            $table->foreignId('current_patient_id')->nullable()->constrained('patients')->onDelete('set null'); // Nullable foreign key
            $table->unique(['room_id', 'bed_identifier']); // Composite unique index
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
