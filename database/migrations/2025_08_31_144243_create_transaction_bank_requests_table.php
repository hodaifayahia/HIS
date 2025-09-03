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
        Schema::create('transaction_bank_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('financial_transactions')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('fiche_navette_item_id')->nullable()->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('item_dependency_id')->nullable();
            
            // Payment details
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['card', 'cheque'])->default('card');
            $table->text('notes')->nullable();
            
            // Request status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_approved')->default(false);
            
            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_bank_requests');
    }
};
