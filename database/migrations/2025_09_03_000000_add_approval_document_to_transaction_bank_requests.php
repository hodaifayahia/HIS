<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalDocumentToTransactionBankRequests extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('transaction_bank_requests', 'approval_document')) {
            Schema::table('transaction_bank_requests', function (Blueprint $table) {
                $table->string('approval_document')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaction_bank_requests', 'approval_document')) {
            Schema::table('transaction_bank_requests', function (Blueprint $table) {
                $table->dropColumn('approval_document');
            });
        }
    }
}
