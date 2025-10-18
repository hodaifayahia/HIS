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
        Schema::create('medications', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing integer
            $table->string('designation', 255)->nullable(); // Changed to 255 to match typical varchar
            $table->string('type_medicament', 255)->nullable();
            $table->string('forme', 255)->nullable();
            $table->text('boite_de')->nullable(); // TEXT type for potentially longer strings
            $table->integer('__v')->nullable(); // Integer for the version field
            $table->boolean('isSelected')->nullable(); // Boolean for the isSelected field
            $table->string('code_pch', 255)->nullable();
            $table->string('nom_commercial', 255)->nullable();
            $table->softDeletes();
            $table->timestamps(); // Laravel's created_at and updated_at (DATETIME)
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
