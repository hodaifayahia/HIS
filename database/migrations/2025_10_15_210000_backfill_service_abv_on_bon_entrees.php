<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * Run the migrations.
     * Backfill service_abv on bon_entrees from services table when missing.
     */
    public function up()
    {
        // Ensure column exists (some environments/migrations may not have added it)
        if (! Schema::hasColumn('bon_entrees', 'service_abv')) {
            Schema::table('bon_entrees', function (Blueprint $table) {
                $table->string('service_abv')->nullable()->after('total_amount');
            });
        }

        // Use a raw update join for portability/performance
        DB::statement(<<<'SQL'
            UPDATE bon_entrees
            JOIN services ON bon_entrees.service_id = services.id
            SET bon_entrees.service_abv = services.service_abv
            WHERE bon_entrees.service_abv IS NULL
              AND bon_entrees.service_id IS NOT NULL
        SQL
        );
    }

    /**
     * Reverse the migrations.
     * This will nullify the service_abv for rows that were set by this migration.
     */
    public function down()
    {
        // revert only the backfilled values
        DB::statement(<<<'SQL'
            UPDATE bon_entrees
            JOIN services ON bon_entrees.service_id = services.id
            SET bon_entrees.service_abv = NULL
            WHERE bon_entrees.service_abv = services.service_abv
        SQL
        );

        // Optionally drop the column if it didn't exist before; however dropping
        // is not safe in all environments so we leave the column in place.
    }
};
