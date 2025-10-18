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
        Schema::table('bon_commends', function (Blueprint $table) {
            $table->longText('pdf_content')->nullable()->after('status');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_commends', function (Blueprint $table) {
            $table->dropColumn(['pdf_content', 'pdf_generated_at']);
        });
    }
};
