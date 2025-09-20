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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('e.g., Hospitalisation, Consultation, Radiology, Operating Theater, Waiting Area');
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade'); // Not null foreign key
            $table->string('room_type', 20)->default('normal');

            $table->timestamps();
        });

        // Note: Seed data should be added via a separate seeder after services table is populated
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};