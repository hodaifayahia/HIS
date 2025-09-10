<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('prescription_medications', function (Blueprint $table) {
            $table->integer('pills_matin')->nullable()->after('description');
            $table->integer('pills_apres_midi')->nullable()->after('pills_matin');
            $table->integer('pills_midi')->nullable()->after('pills_apres_midi');
            $table->integer('pills_soir')->nullable()->after('pills_midi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('prescription_medications', function (Blueprint $table) {
            $table->dropColumn('pills_matin');
            $table->dropColumn('pills_apres_midi');
            $table->dropColumn('pills_midi');
            $table->dropColumn('pills_soir');
        });
    }
};