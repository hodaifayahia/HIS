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
        Schema::table('fiche_navettes', function (Blueprint $table) {
            $table->foreignId('companion_id')->nullable()->constrained('patients')->after('patient_id')->onDelete('cascade')->comment('The companion associated with the fiche navette');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navettes', function (Blueprint $table) {
            $table->dropForeign(['companitor_id']);
            $table->dropColumn('companitor_id');
        });
    }
};
