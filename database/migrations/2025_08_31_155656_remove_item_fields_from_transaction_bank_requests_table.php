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
        Schema::table('transaction_bank_requests', function (Blueprint $table) {
            // Remove the item-specific columns since they'll be accessed through transaction relationship
            $table->dropForeign(['fiche_navette_item_id']);
            $table->dropColumn('fiche_navette_item_id');
            $table->dropColumn('item_dependency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_bank_requests', function (Blueprint $table) {
            // Restore the columns if rollback is needed
            $table->foreignId('fiche_navette_item_id')->nullable()->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('item_dependency_id')->nullable();
        });
    }
};
