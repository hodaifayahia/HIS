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
            if (! Schema::hasColumn('fiche_navette_items', 'is_nursing_consumption')) {
                $table->boolean('is_nursing_consumption')
                    ->default(false)
                    ->after('package_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            if (Schema::hasColumn('fiche_navette_items', 'is_nursing_consumption')) {
                $table->dropColumn('is_nursing_consumption');
            }
        });
    }
};
