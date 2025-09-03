<?php
// database/migrations/2025_01_21_create_caisse_session_denominations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_session_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_session_id')->constrained('caisse_sessions')->onDelete('cascade');
            $table->enum('denomination_type', ['coin', 'note']); // Piece or Billet
            $table->decimal('value', 10, 2); // 0.50, 1, 5, 10, 20, 50, 100, 200, 500, 1000, 2000
            $table->integer('quantity'); // Number of pieces/notes
            $table->decimal('total_amount', 15, 2); // value * quantity (calculated)
            $table->timestamps();

            // Indexes
            $table->index(['caisse_session_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_session_denominations');
    }
};
