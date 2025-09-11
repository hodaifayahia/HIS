<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')
                  ->unique()
                  ->comment('e.g., Hospitalisation, Consultation, Radiology, Operating Theater, Waiting Area');
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();

            $table->foreignId('service_id')
                  ->nullable()
                  ->constrained('services')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
