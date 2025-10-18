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
        Schema::create('fiche_navettes', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('The user who created the fiche'); // Not null foreign key
            $table->date('fiche_date'); // Not null
          //total_amount
            $table->decimal('total_amount', 15, 2)->default(0); // Not null, default to 0
            $table->string('status')->comment('e.g., scheduled, checked-in, completed, cancelled'); // Not null
            $table->string('reference')->nullable(); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_navettes');
    }
};
