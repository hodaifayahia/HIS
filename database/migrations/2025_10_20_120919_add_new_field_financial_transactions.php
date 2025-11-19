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
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->boolean('is_bank_transaction')->nullable();
            $table->foreignId('bank_id_account')->nullable()->constrained('bank_accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_id_account']);
            $table->dropColumn(['bank_id_account' , 'is_bank_transaction']);
        });
    }
};
