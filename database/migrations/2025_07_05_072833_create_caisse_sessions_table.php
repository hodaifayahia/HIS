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
          Schema::create('medication_doctor_favorats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // The cashier
            $table->dateTime('ouverture_at');
            $table->dateTime('cloture_at')->nullable();
            $table->decimal('opening_amount', 15, 2);
            $table->decimal('closing_amount', 15, 2)->nullable();
            $table->decimal('expected_closing_amount', 15, 2)->nullable(); // Calculated from transactions
            $table->string('status')->default('open'); // e.g., open, closed, reconciled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_doctor_favorats');
    }
};
