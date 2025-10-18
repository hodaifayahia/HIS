<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add bon_entree_id to bon_receptions table
        Schema::table('bon_receptions', function (Blueprint $table) {
            $table->unsignedBigInteger('bon_entree_id')->nullable()->after('bon_retour_id');
            $table->index('bon_entree_id');
            
            $table->foreign('bon_entree_id')
                ->references('id')
                ->on('bon_entrees')
                ->onDelete('set null');
        });

        // Add bon_reception_item_id to bon_entree_items for linking
        Schema::table('bon_entree_items', function (Blueprint $table) {
            $table->unsignedBigInteger('bon_reception_item_id')->nullable()->after('product_id');
            $table->index('bon_reception_item_id');
            
            $table->foreign('bon_reception_item_id')
                ->references('id')
                ->on('bon_reception_items')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            $table->dropForeign(['bon_entree_id']);
            $table->dropColumn('bon_entree_id');
        });

        Schema::table('bon_entree_items', function (Blueprint $table) {
            $table->dropForeign(['bon_reception_item_id']);
            $table->dropColumn('bon_reception_item_id');
        });
    }
};
