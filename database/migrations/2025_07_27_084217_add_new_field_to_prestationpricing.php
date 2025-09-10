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
        Schema::table('prestation_pricing', function (Blueprint $table) {
            // add subname field to prestation_pricing table
            // $table->string('subname')->nullable()->after('annex_id');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestation_pricing', function (Blueprint $table) {
            // drop subname field from prestation_pricing table
            $table->dropColumn('subname');
        });
    }
};
