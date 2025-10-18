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
          Schema::create('ayants_droit_rules', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('convention_id')->nullable()->constrained('conventions')->onDelete('cascade'); // Nullable foreign key
            $table->foreignId('avenant_id')->nullable()->constrained('avenants')->onDelete('cascade'); // Nullable foreign key
            $table->string('relationship_type')->comment('e.g., Spouse, Child'); // Not null
            $table->integer('max_age')->nullable(); // Nullable integer
            $table->boolean('is_covered')->default(true); // Not null with default
            $table->timestamps(); // Adds created_at and updated_at columns (implied, good practice)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ayants_droit_rules');
    }
};
