<?php
// database/migrations/create_bank_account_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->foreignId('accepted_by_user_id')->constrained('users')->onDelete('restrict');
            $table->enum('transaction_type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->datetime('transaction_date');
            $table->text('description')->nullable();
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'completed', 'cancelled', 'reconciled'])->default('pending');
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
            
            $table->index(['bank_account_id', 'status']);
            $table->index(['transaction_type', 'status']);
            $table->index('transaction_date');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_transactions');
    }
};
