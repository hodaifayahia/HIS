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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('fiche_navette_item_id')->nullable()->constrained('fiche_navette_items')->onDelete('set null'); // Nullable foreign key
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->decimal('amount', 15, 2); // Not null
            $table->string('transaction_type')->comment('payment, refund, deposit, withdrawal, donation'); // Not null
            $table->string('payment_method')->nullable()->comment('cash, card, patient-balance, bank-transfer'); // Nullable string
            $table->foreignId('b2b_invoice_id')->nullable()->constrained('b2b_invoices')->onDelete('set null'); // Nullable foreign key
            $table->text('notes')->nullable(); // Nullable text
            $table->timestamps(); // Not null with default current timestamp
            // No updated_at as per diagram
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
