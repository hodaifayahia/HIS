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
        Schema::create('contract_percentages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('conventions')->onDelete('cascade');
            $table->decimal('percentage', 5, 2); // e.g., 20.00 for 20%
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_percentages');
    }
};
