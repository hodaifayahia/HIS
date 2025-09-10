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
        Schema::create('remise_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remise_id')->constrained('remises')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->unique(['remise_id', 'user_id']); // New: Prevent duplicate entries

            // $table->string('payment_method')->nullable(); // If you need this, uncomment and define purpose
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remise_users');
    }
};