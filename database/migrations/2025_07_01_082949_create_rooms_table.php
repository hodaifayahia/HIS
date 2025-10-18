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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('pavilion_id')->nullable()->constrained('pavilions')->onDelete('set null'); // Nullable foreign key
            $table->string('name')->comment('e.g., Room 201, Consultation Box 5, Operating Theater 1'); // Not null
            $table->string('room_type')->comment('hospitalization, consultation, operating_theater, waiting_area'); // Not null
            $table->decimal('nightly_price', 15, 2)->nullable(); // Nullable decimal
            $table->foreignId('service_id')->constrained();
            $table->string('status')->default('free')->comment('free, occupied, cleaning, maintenance'); // Not null with default
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
