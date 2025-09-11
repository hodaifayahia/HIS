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
       Schema::create('coffre_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffre_id')->constrained('coffres')->onDelete('cascade');
            
            $table->foreignId('user_id')->constrained('users'); // User performing the transaction
            $table->string('transaction_type'); // e.g., deposit, withdrawal, transfer_to_bank, transfer_from_caisse
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('source_caisse_session_id')->nullable()->constrained('caisse_sessions')->onDelete('set null'); // If transfer from caisse
            $table->foreignId('destination_banque_id')->nullable()->constrained('banks')->onDelete('set null'); // If transfer to bank
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffre_transactions');
    }
};
