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
        Schema::create('stockage_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stockage_id')->constrained()->onDelete('cascade');
            $table->enum('tool_type', ['RY', 'AR', 'CF', 'FR', 'CS', 'CH', 'PL']);
            $table->integer('tool_number');
            $table->char('block', 1)->nullable(); // Only for Rayonnage (RY)
            $table->integer('shelf_level')->nullable(); // Only for Rayonnage (RY)
            $table->timestamps();

            // Add unique constraint to prevent duplicate tool numbers within same stockage
            $table->unique(['stockage_id', 'tool_type', 'tool_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stockage_tools');
    }
};
