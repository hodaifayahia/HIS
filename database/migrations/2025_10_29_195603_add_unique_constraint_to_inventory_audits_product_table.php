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
        // Check if product_type column exists before removing duplicates
        if (Schema::hasColumn('inventory_audits_product', 'product_type')) {
            // First, remove any duplicate records (keep the latest one)
            DB::statement('
                DELETE t1 FROM inventory_audits_product t1
                INNER JOIN inventory_audits_product t2 
                WHERE t1.id < t2.id 
                AND t1.inventory_audit_id = t2.inventory_audit_id 
                AND t1.product_id = t2.product_id
                AND t1.product_type = t2.product_type
            ');

            Schema::table('inventory_audits_product', function (Blueprint $table) {
                // Add unique constraint on the combination of audit_id, product_id, and product_type
                $table->unique(['inventory_audit_id', 'product_id', 'product_type'], 'unique_audit_product');
            });
        } else {
            // If product_type doesn't exist, use a simpler constraint without it
            // First remove duplicates without product_type
            DB::statement('
                DELETE t1 FROM inventory_audits_product t1
                INNER JOIN inventory_audits_product t2 
                WHERE t1.id < t2.id 
                AND t1.inventory_audit_id = t2.inventory_audit_id 
                AND t1.product_id = t2.product_id
            ');

            Schema::table('inventory_audits_product', function (Blueprint $table) {
                // Add unique constraint without product_type
                if (!$this->hasUniqueConstraint('inventory_audits_product', ['inventory_audit_id', 'product_id'])) {
                    $table->unique(['inventory_audit_id', 'product_id'], 'unique_audit_product');
                }
            });
        }
    }

    /**
     * Check if unique constraint exists
     */
    private function hasUniqueConstraint(string $table, array $columns): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === 'unique_audit_product') {
                return true;
            }
        }
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_audits_product', function (Blueprint $table) {
            $table->dropUnique('unique_audit_product');
        });
    }
};
