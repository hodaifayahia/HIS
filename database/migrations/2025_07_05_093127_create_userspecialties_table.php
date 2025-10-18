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
        Schema::create('user_specialties', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Link to the doctor/staff'); // Not null foreign key
            $table->foreignId('specialization_id')->constrained('specializations')->onDelete('cascade')->comment('Link to the clinical specialty'); // Not null foreign key
           $table->primary(['user_id', 'specialization_id']); // Composite primary key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_specialties');
    }
};
