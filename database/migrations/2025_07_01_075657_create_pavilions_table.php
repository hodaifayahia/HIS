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
      Schema::create('pavilions', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->string('name', 191)->unique()->comment('e.g., Surgical Wing, Outpatient Wing'); // Unique and not null
            $table->text('description')->nullable(); // Nullable text field
            $table->text('service_id')->nullable(); // Nullable text field
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pavilions');
    }
};
