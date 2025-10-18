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
            $table->string('name')->unique()->comment('e.g., Hospitalisation, Consultation, Radiology, Operating Theater, Waiting Area');
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade'); // Not null foreign key
           $table->string('room_type', 20)
                    ->default('normal')
                    ->after('service_id');

            $table->timestamps();
        });

        // Seed initial data for room types
        \DB::table('room_types')->insert([
            ['name' => 'Hospitalisation', 'description' => 'Rooms for patients admitted for overnight stays.'],
            ['name' => 'Consultation', 'description' => 'Individual rooms where doctors meet with patients.'],
            ['name' => 'Treatment', 'description' => 'Rooms for minor procedures, wound care, injections etc.'],
            ['name' => 'Radiology', 'description' => 'Shielded rooms that house specific imaging equipment.'],
            ['name' => 'Cath Lab', 'description' => 'Highly specialized hybrid rooms for cardiac and interventional radiology procedures.'],
            ['name' => 'Operating Theater', 'description' => 'Critical sterile environments for surgery.'],
            ['name' => 'Waiting Area', 'description' => 'Areas where patients wait before their appointments.'],
            ['name' => 'Transit Area', 'description' => 'Areas for patient movement between departments.'],
            // Add other types as needed
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
