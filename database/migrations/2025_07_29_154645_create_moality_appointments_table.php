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
        Schema::create('moality_appointments', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('modality_id')
                  ->constrained('modalities') // Assumes 'modalities' table exists
                  ->onDelete('cascade'); // If a modality is deleted, associated appointments are deleted

            $table->foreignId('patient_id')
                  ->constrained('patients') // Assumes 'users' table and patient is a user, adjust if patient is in a different table
                  ->onDelete('cascade'); // If a patient is deleted, associated appointments are deleted

            $table->text('notes')->nullable();
            
            // For 'appointment_booking_window', it could be a datetime range, or calculated from date/time
            // If it's a specific window ID or reference, adjust type and add foreign key
            $table->string('appointment_booking_window')->nullable(); // Example as string, adjust type if it's a specific ID

            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('reason')->nullable(); // Reason for the appointment

            // User tracking for who created/canceled/updated the appointment
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users') // Assumes 'users' table, adjust if necessary
                  ->onDelete('set null'); // If user is deleted, set created_by to null

            $table->foreignId('canceled_by')
                  ->nullable()
                  ->constrained('users') // Assumes 'users' table, adjust if necessary
                  ->onDelete('set null'); // If user is deleted, set canceled_by to null

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users') // Assumes 'users' table, adjust if necessary
                  ->onDelete('set null'); // If user is deleted, set updated_by to null

            $table->string('status')->default('pending'); // e.g., 'pending', 'confirmed', 'canceled', 'completed'

            $table->timestamps(); // Adds `created_at` and `updated_at` columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modality_appointments');
    }
};