<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Make product_id nullable and add pharmacy_product_id
        DB::statement("ALTER TABLE `factureproforma_products` MODIFY `product_id` bigint unsigned NULL");
        Schema::table('factureproforma_products', function ($table) {
            if (! Schema::hasColumn('factureproforma_products', 'pharmacy_product_id')) {
                $table->unsignedBigInteger('pharmacy_product_id')->nullable()->after('product_id')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('factureproforma_products', function ($table) {
            if (Schema::hasColumn('factureproforma_products', 'pharmacy_product_id')) {
                $table->dropColumn('pharmacy_product_id');
            }
        });

        // Make product_id NOT NULL again (set default 0 to avoid errors) - this is destructive if nulls exist.
        // We avoid forcing a non-nullable revert to be safe; admins should handle reverting manually if needed.
        // DB::statement("ALTER TABLE `factureproforma_products` MODIFY `product_id` bigint unsigned NOT NULL");
    }
};
