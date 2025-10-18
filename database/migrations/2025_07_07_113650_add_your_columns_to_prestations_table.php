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
        Schema::table('prestations', function (Blueprint $table) {
            $table->decimal('Tarif_de_nuit', 10, 2)->default(0);
            $table->boolean('Tarif_de_nuit_is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropColumn('Tarif_de_nuit');
            $table->dropColumn('Tarif_de_nuit_is_active');
        });
    }
};
