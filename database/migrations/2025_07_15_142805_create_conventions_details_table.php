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
        Schema::create('conventions_details', function (Blueprint $table) {
            $table->id();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // Changed to JSON column as requested, allowing for flexible data storage.
            $table->json('family_auth')->nullable(); 
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->foreignId('avenant_id')->constrained('avenants')->onDelete('cascade'); // Added onDelete for referential integrity
             $table->foreignId('convention_id')->constrained('conventions');

            $table->softDeletes(); // Added soft deletes for logical deletion
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conventions_details');
    }
};
