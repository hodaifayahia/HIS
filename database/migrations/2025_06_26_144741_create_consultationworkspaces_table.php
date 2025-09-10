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
        Schema::create('consultationworkspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
             $table->timestamp('last_accessed')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultationworkspaces');
    }
};
