<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('consignment_reception_items')) {
            return;
        }

        if (! $this->indexExists('consignment_reception_items', 'consignment_items_unique')) {
            return;
        }

        Schema::table('consignment_reception_items', function (Blueprint $table) {
            // Drop the unique constraint that prevents same product from appearing multiple times
            $table->dropUnique('consignment_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('consignment_reception_items')) {
            return;
        }

        if ($this->indexExists('consignment_reception_items', 'consignment_items_unique')) {
            return;
        }

        Schema::table('consignment_reception_items', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique(['consignment_reception_id', 'product_id'], 'consignment_items_unique');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();

        if ($driverName === 'sqlite') {
            $results = DB::select("PRAGMA index_list('{$table}')");

            foreach ($results as $result) {
                if (($result->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $databaseName = $connection->getDatabaseName();

        $result = DB::connection($connection->getName())->select(
            'SELECT COUNT(*) as `count`
             FROM information_schema.statistics
             WHERE table_schema = ?
             AND table_name = ?
             AND index_name = ?',
            [$databaseName, $table, $index]
        );

        return ! empty($result) && $result[0]->count > 0;
    }
};
