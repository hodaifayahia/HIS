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
        Schema::table('prestation_pricing', function (Blueprint $table) {
            $table->foreignId('contract_percentage_id')->nullable()->constrained('contract_percentages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestation_pricing', function (Blueprint $table) {
            $table->dropForeign(['contract_percentage_id']);
            $table->dropColumn('contract_percentage_id');
        });
    }
};
