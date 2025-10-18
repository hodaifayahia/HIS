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
        Schema::create('specialty_minimum_rules', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('convention_id')->nullable()->constrained('conventions')->onDelete('cascade'); // Nullable foreign key
            $table->foreignId('avenant_id')->nullable()->constrained('avenants')->onDelete('cascade'); // Nullable foreign key
            $table->foreignId('specialization_id')->nullable()->constrained('specializations')->onDelete('cascade'); // Nullable foreign key
            $table->decimal('minimum_required_amount', 15, 2); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns (implied, good practice)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialty_minimum_rules');
    }
};
