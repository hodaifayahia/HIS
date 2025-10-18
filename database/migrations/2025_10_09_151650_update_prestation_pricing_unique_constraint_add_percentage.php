<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Since we can't drop the existing unique constraint due to foreign key dependencies,
        // we'll modify the approach. The existing constraint allows one pricing per prestation/annex.
        // With multiple percentages, we need multiple pricings per prestation/annex.
        // Since contract_percentage_id is nullable, we'll allow NULL values in the unique constraint.

        // Drop and recreate with the new composite unique constraint
        DB::statement('ALTER TABLE prestation_pricing DROP INDEX prestation_pricing_prestation_id_annex_id_unique, ADD UNIQUE KEY prestation_pricing_unique (prestation_id, annex_id, contract_percentage_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint and recreate the old one
        DB::statement('ALTER TABLE prestation_pricing DROP INDEX prestation_pricing_unique');
        DB::statement('ALTER TABLE prestation_pricing ADD UNIQUE KEY prestation_pricing_prestation_id_annex_id_unique (prestation_id, annex_id)');
    }
};
