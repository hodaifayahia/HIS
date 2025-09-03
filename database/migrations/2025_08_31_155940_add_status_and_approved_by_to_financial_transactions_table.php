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
            // Add status field for tracking transaction state
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed')->after('notes');
            
            // Add approved_by field for tracking who approved card/cheque payments
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('cashier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_by']);
        });
    }
};
