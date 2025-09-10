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
        Schema::create('prestation_remises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remise_id')->constrained('remises')->onDelete('cascade');
            $table->foreignId('prestation_id')->constrained('prestations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestation_remises');
    }
};
