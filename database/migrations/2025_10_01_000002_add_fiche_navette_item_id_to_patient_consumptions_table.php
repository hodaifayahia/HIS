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
        Schema::table('patient_consumptions', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_consumptions', 'fiche_navette_item_id')) {
                $table->foreignId('fiche_navette_item_id')
                    ->nullable()
                    ->after('fiche_id')
                    ->constrained('fiche_navette_items')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_consumptions', function (Blueprint $table) {
            if (Schema::hasColumn('patient_consumptions', 'fiche_navette_item_id')) {
                $table->dropForeign(['fiche_navette_item_id']);
                $table->dropColumn('fiche_navette_item_id');
            }
        });
    }
};
