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
            // Add tva_const_prestation column after vat_rate
            // This is a separate VAT rate specifically for consumables when different from general vat_rate
            $table->decimal('tva_const_prestation', 8, 2)->nullable()->after('vat_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropColumn('tva_const_prestation');
        });
    }
};
