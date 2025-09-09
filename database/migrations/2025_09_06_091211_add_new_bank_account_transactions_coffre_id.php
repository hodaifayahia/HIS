<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
// In your migration file: 2025_09_06_091211_add_new_bank_account_transactions_coffre_id
Schema::table('bank_account_transactions', function (Blueprint $table) {
    $table->foreignId('coffre_id')
          ->nullable() // This is the key change
          ->constrained('coffres')
          ->onDelete('no action');
});
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->dropForeign(['coffre_id']);
            $table->dropColumn('coffre_id');
        });
    }
};