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
         Schema::create('banque_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banque_id')->constrained('banques')->onDelete('cascade');
            $table->string('transaction_type'); // e.g., deposit, withdrawal, transfer, payment
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable(); // Bank statement reference
            $table->string('status')->default('pending_reconciliation'); // pending_reconciliation, reconciled, failed
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banque_transactions');
    }
};
