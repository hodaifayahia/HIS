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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['Medical Supplies', 'Equipment', 'Medication', 'Others']);
            $table->boolean('is_clinical')->default(false); // true for medications

            // Medication-specific fields
            $table->integer('code_interne')->nullable();
            $table->string('code_pch')->nullable();
            $table->string('designation')->nullable();
            $table->string('type_medicament')->nullable();
            $table->string('forme')->nullable();
            $table->integer('boite_de')->nullable();
            $table->string('nom_commercial')->nullable();

            $table->enum('status', ['In Stock', 'Low Stock', 'Out of Stock'])->default('In Stock');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
