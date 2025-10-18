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
            $table->unsignedBigInteger('companion_id')->nullable()->after('patient_id');
            $table->foreign('companion_id')->references('id')->on('patients')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navettes', function (Blueprint $table) {
            $table->dropForeign(['companion_id']);
            $table->dropColumn('companion_id');
        });
    }
};
