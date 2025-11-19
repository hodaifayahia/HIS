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
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (! Schema::hasColumn('fiche_navette_items', 'consignment_item_id')) {
                $table->unsignedBigInteger('consignment_item_id')
                    ->nullable()
                    ->after('fiche_navette_id');

                $table->foreign('consignment_item_id')
                    ->references('id')
                    ->on('consignment_reception_items')
                    ->onDelete('set null');

                $table->index('consignment_item_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            if (Schema::hasColumn('fiche_navette_items', 'consignment_item_id')) {
                $table->dropForeign(['consignment_item_id']);
                $table->dropIndex(['consignment_item_id']);
                $table->dropColumn('consignment_item_id');
            }
        });
    }
};
