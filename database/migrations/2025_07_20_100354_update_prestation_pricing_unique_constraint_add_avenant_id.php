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
            // 1. Drop the existing unique index
            // You might need to find the exact name of your unique index.
            // It's often something like 'table_name_column1_column2_unique'
            // Based on your error, it's likely 'prestation_pricing_prestation_id_annex_id_unique'.
            // // Verify this in your previous migration file or by inspecting your database.
            // $table->dropUnique('prestation_pricing_prestation_id_annex_id_unique');

            // // 2. Add a new unique index that includes 'avenant_id'
            // $table->unique(['prestation_id', 'annex_id', 'avenant_id'], 'prestation_pricing_prestation_id_annex_id_avenant_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestation_pricing', function (Blueprint $table) {
            // Drop the new unique index
            $table->dropUnique('prestation_pricing_prestation_id_annex_id_avenant_id_unique');

            // Re-add the old unique index if you ever need to roll back
            $table->unique(['prestation_id', 'annex_id'], 'prestation_pricing_prestation_id_annex_id_unique');
        });
    }
};