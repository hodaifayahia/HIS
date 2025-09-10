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
       Schema::create('caisse_transactions', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('caisse_session_id')->constrained('caisse_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // User performing the transaction
            $table->string('transaction_type'); // e.g., payment_in, refund_out, expense_out, cash_deposit
            $table->string('payment_method'); // e.g., cash, card, check, transfer
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->nullableMorphs('sourceable'); // Polymorphic link to the origin (e.g., FicheNavetteItem)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_transactions');
    }
};
