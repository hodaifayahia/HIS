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
        Schema::table('bon_receptions', function (Blueprint $table) {
            // Fix nombre_colis data type - should be integer, not timestamp
            $table->integer('nombre_colis')->change();

            // Fix observation data type - should be text, not varchar
            $table->text('observation')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            $table->dropColumn(['nombre_colis', 'observation']);
        });
    }
};
