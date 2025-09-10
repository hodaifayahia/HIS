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
        Schema::table('modalities', function (Blueprint $table) {
            // type of consumption
            $table->string('consumption_type')->nullable(); // Adding new field 'consumption_type'
            $table->integer('consumption_unit')->nullable()->after('consumption_type'); // Adding new field 'consumption_type' as integer
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            $table->dropColumn('consumption_type');
            $table->dropColumn('consumption_unit');
        });
    }
};
