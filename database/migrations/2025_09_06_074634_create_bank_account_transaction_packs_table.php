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
        Schema::create('bank_account_transaction_packs', function (Blueprint $table) {
            $table->id();
            // use explicit unsignedBigInteger and set a short foreign key name to avoid MySQL identifier length limits
            $table->unsignedBigInteger('bank_account_transaction_id');
            $table->foreign('bank_account_transaction_id', 'fk_batp_transaction')
                  ->references('id')
                  ->on('bank_account_transactions')
                  ->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->boolean('has_packs')->default(false);
            $table->string('reference')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_account_transaction_packs');
    }
};