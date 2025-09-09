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
        Schema::table('coffre_transactions', function (Blueprint $table) {
            // $table->string('status')->default('pending');
            // $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('no action');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coffre_transactions', function (Blueprint $table) {
            //
        });
    }
};
