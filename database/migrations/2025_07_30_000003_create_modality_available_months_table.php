<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modality_available_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modality_id')->constrained('modalities')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->boolean('is_available')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            // Ensure unique combination of modality, month, and year
            // $table->unique(['modality_id', 'month', 'year', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modality_available_months');
    }
};
