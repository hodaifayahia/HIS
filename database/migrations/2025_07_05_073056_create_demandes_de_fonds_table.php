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
        Schema::create('demandes_de_fonds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->string('nature'); // e.g., "supplier_payment", "petty_cash", "inter_caisse_transfer"
            $table->text('justification')->nullable();
            $table->string('status')->default('pending'); // e.g., pending, approved, rejected, disbursed
            $table->foreignId('disbursed_from_coffre_id')->nullable()->constrained('coffres')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_de_fonds');
    }
};
