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
        Schema::create('approval_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('max_amount', 15, 2); // Maximum amount this person can approve
            $table->string('title')->nullable(); // Job title or position
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Lower priority number = higher authority
            $table->timestamps();

            // Index for faster queries
            $table->index(['is_active', 'max_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_persons');
    }
};
