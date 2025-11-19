<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update unique constraint to include participant_id
     * This allows each participant to have their own count for the same product
     */
    public function up(): void
    {
        // Check if product_type column exists
        if (Schema::hasColumn('inventory_audits_product', 'product_type')) {
            // First, remove any duplicate records per participant (keep the latest one)
            DB::statement('
                DELETE t1 FROM inventory_audits_product t1
                INNER JOIN inventory_audits_product t2 
                WHERE t1.id < t2.id 
                AND t1.inventory_audit_id = t2.inventory_audit_id 
                AND t1.product_id = t2.product_id
                AND t1.product_type = t2.product_type
                AND t1.participant_id = t2.participant_id
            ');

            Schema::table('inventory_audits_product', function (Blueprint $table) {
                // Drop old unique constraint if exists
                try {
                    $table->dropUnique('unique_audit_product');
                } catch (\Exception $e) {
                    // Constraint doesn't exist, continue
                }
                
                // Add new unique constraint including participant_id
                // This ensures: one count per (audit + product + product_type + participant)
                $table->unique(
                    ['inventory_audit_id', 'product_id', 'product_type', 'participant_id'], 
                    'unique_audit_product_participant'
                );
            });
        } else {
            // If product_type doesn't exist, use constraint without it
            DB::statement('
                DELETE t1 FROM inventory_audits_product t1
                INNER JOIN inventory_audits_product t2 
                WHERE t1.id < t2.id 
                AND t1.inventory_audit_id = t2.inventory_audit_id 
                AND t1.product_id = t2.product_id
                AND IFNULL(t1.participant_id, 0) = IFNULL(t2.participant_id, 0)
            ');

            Schema::table('inventory_audits_product', function (Blueprint $table) {
                // Drop old unique constraint if exists
                try {
                    $table->dropUnique('unique_audit_product');
                } catch (\Exception $e) {
                    // Constraint doesn't exist, continue
                }
                
                // Check if the new constraint already exists
                $indexExists = DB::select("
                    SELECT COUNT(*) as count 
                    FROM information_schema.statistics 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'inventory_audits_product' 
                    AND index_name = 'unique_audit_product_participant'
                ");
                
                if ($indexExists[0]->count == 0) {
                    // Add new unique constraint without product_type
                    $table->unique(
                        ['inventory_audit_id', 'product_id', 'participant_id'], 
                        'unique_audit_product_participant'
                    );
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_audits_product', function (Blueprint $table) {
            // Drop new constraint
            $table->dropUnique('unique_audit_product_participant');
            
            // Restore old constraint (without participant_id)
            $table->unique(['inventory_audit_id', 'product_id', 'product_type'], 'unique_audit_product');
        });
    }
};
