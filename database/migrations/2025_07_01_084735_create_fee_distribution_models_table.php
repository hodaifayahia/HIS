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
         Schema::create('fee_distribution_models', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('prestation_id')->nullable()->constrained('prestations')->onDelete('cascade'); // Nullable foreign key
            $table->string('role_name')->nullable()->comment('e.g., Primary Doctor, Assistant, Technician, Clinic'); // Nullable string
            $table->string('share_type')->nullable()->comment('percentage, fixed_amount'); // Nullable string
            $table->decimal('share_value', 15, 2); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns (implied, good practice)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_distribution_models');
    }
};
