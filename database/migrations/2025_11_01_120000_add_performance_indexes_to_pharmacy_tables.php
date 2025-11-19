<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - ADD PERFORMANCE INDEXES
     */
    public function up(): void
    {
        // Use raw SQL to avoid index conflicts
        try {
            // Pharmacy Inventories Indexes
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_inventories_quantity_idx ON pharmacy_inventories(quantity)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_inventories_expiry_date_idx ON pharmacy_inventories(expiry_date)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_inventories_product_stockage_idx ON pharmacy_inventories(pharmacy_product_id, pharmacy_stockage_id)');
            
            // Pharmacy Products Indexes
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_products_name_idx ON pharmacy_products(name)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_products_code_idx ON pharmacy_products(code)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_products_category_idx ON pharmacy_products(category)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_products_is_active_idx ON pharmacy_products(is_active)');
            
            // Pharmacy Stockages Indexes
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_stockages_name_idx ON pharmacy_stockages(name)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_stockages_type_idx ON pharmacy_stockages(type)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_stockages_status_idx ON pharmacy_stockages(status)');
            DB::statement('CREATE INDEX IF NOT EXISTS pharmacy_stockages_service_id_idx ON pharmacy_stockages(service_id)');
        } catch (\Exception $e) {
            // Indexes may already exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        try {
            DB::statement('DROP INDEX IF EXISTS pharmacy_inventories_quantity_idx ON pharmacy_inventories');
            DB::statement('DROP INDEX IF EXISTS pharmacy_inventories_expiry_date_idx ON pharmacy_inventories');
            DB::statement('DROP INDEX IF EXISTS pharmacy_inventories_product_stockage_idx ON pharmacy_inventories');
            
            DB::statement('DROP INDEX IF EXISTS pharmacy_products_name_idx ON pharmacy_products');
            DB::statement('DROP INDEX IF EXISTS pharmacy_products_code_idx ON pharmacy_products');
            DB::statement('DROP INDEX IF EXISTS pharmacy_products_category_idx ON pharmacy_products');
            DB::statement('DROP INDEX IF EXISTS pharmacy_products_is_active_idx ON pharmacy_products');
            
            DB::statement('DROP INDEX IF EXISTS pharmacy_stockages_name_idx ON pharmacy_stockages');
            DB::statement('DROP INDEX IF EXISTS pharmacy_stockages_type_idx ON pharmacy_stockages');
            DB::statement('DROP INDEX IF EXISTS pharmacy_stockages_status_idx ON pharmacy_stockages');
            DB::statement('DROP INDEX IF EXISTS pharmacy_stockages_service_id_idx ON pharmacy_stockages');
        } catch (\Exception $e) {
            // Continue even if drop fails
        }
    }
};
