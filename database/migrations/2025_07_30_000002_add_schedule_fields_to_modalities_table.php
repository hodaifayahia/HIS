<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->enum('frequency', ['Daily', 'Weekly', 'Monthly', 'Custom'])->default('Weekly');
            $table->string('image_path')->nullable()->comment('Path to the modality image');
            $table->integer('time_slot_duration')->nullable()->comment('Default duration in minutes for this modality');
            $table->enum('slot_type', ['minutes', 'days'])->default('minutes');
            $table->integer('booking_window')->default(30)->comment('How many days in advance can appointments be booked');
            $table->json('availability_months')->nullable()->comment('JSON array of available months for booking');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->dropColumn([
                'frequency',
                'time_slot_duration',
                'slot_type',
                'booking_window',
                'availability_months',
                'is_active',
                'notes'
            ]);
        });
    }
};
