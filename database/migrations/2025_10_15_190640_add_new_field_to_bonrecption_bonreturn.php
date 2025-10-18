<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            $table->unsignedBigInteger('bon_retour_id')->nullable()->after('bon_commend_id');
            $table->index('bon_retour_id');
            
            $table->foreign('bon_retour_id')
                ->references('id')
                ->on('bon_retours')
                ->onDelete('set null');
        });

        // Add bon_reception_item_id to bon_retour_items for linking
        Schema::table('bon_retour_items', function (Blueprint $table) {
            $table->unsignedBigInteger('bon_reception_item_id')->nullable()->after('bon_entree_item_id');
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
            $table->dropForeign(['bon_retour_id']);
            $table->dropColumn('bon_retour_id');
        });

        Schema::table('bon_retour_items', function (Blueprint $table) {
            $table->dropForeign(['bon_reception_item_id']);
            $table->dropColumn('bon_reception_item_id');
        });
    }
};
