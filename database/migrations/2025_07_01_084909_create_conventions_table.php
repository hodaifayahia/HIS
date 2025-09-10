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
       Schema::create('conventions', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('organisme_id')->constrained('organismes')->onDelete('cascade'); // Not null foreign key
            $table->string('name'); // Not null
            $table->date('start_date'); // Not null
            $table->date('end_date'); // Not null
            $table->date('is_general'); // Not null
            $table->string('status')->default('active'); // Not null with default
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conventions');
    }
};
