<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Note: we add the column nullable and without a foreign key to be safe
     * in environments where pharmacy tables may not yet be present.
     */
    public function up()
    {
        Schema::table('service_demand_purchasing_items', function (Blueprint $table) {
            if (! Schema::hasColumn('service_demand_purchasing_items', 'pharmacy_product_id')) {
                $table->unsignedBigInteger('pharmacy_product_id')->nullable()->after('product_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('service_demand_purchasing_items', function (Blueprint $table) {
            if (Schema::hasColumn('service_demand_purchasing_items', 'pharmacy_product_id')) {
                $table->dropColumn('pharmacy_product_id');
            }
        });
    }
};
