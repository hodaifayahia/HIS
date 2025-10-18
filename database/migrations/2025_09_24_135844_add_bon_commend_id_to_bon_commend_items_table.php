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
        Schema::table('bon_commend_items', function (Blueprint $table) {
            $table->foreignId('bon_commend_id')->nullable()->after('id')->constrained('bon_commends')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_commend_items', function (Blueprint $table) {
            $table->dropForeign(['bon_commend_id']);
            $table->dropColumn('bon_commend_id');
        });
    }
};
