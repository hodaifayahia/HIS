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
            $table->foreignId('package_id')->nullable()->constrained('prestation_packages')->onDelete('set null')->after('prestation_id'); // Nullable foreign key for package_id
            $table->foreignId('remise_id')->nullable()->constrained('remises')->onDelete('set null')->after('package_id'); // Nullable foreign key for remise_id
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });
    }
};
