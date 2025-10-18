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
        Schema::create('salls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number');
            $table->text('description')->nullable();
            $table->foreignId('defult_specialization_id')->nullable()->constrained('specializations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salls');
    }
};
