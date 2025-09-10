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
        Schema::create('remises', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('code')->nullable()->unique(); // 'code' should likely be unique
            
            // Using unsignedBigInteger and index for foreign key with nullable()
            $table->unsignedBigInteger('prestation_id')->nullable();
            $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('set null'); // Use set null if a prestation can be deleted

            $table->decimal('amount', 10, 2)->nullable(); // Amount for fixed type remises
            $table->decimal('percentage', 5, 2)->nullable(); // Percentage for percentage type remises
            
            // Enforce that only one type of discount can be applied
            $table->enum('type', ['fixed', 'percentage'])->default('fixed'); // New: 'type' column

            $table->boolean('is_active')->default(true);
            
            // Removed redundant boolean flags, 'type' handles this
            // $table->boolean('is_percentage')->default(false); 
            // $table->boolean('is_fixed')->default(false);

            $table->timestamps(); // Added timestamps for better tracking
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remises');
    }
};