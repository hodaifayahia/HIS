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
        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            if (! Schema::hasColumn('service_demand_purchasings', 'is_pharmacy')) {
                $table->boolean('is_pharmacy')->default(false)->after('status')->comment('True if demand is for pharmacy products, false if for stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            if (Schema::hasColumn('service_demand_purchasings', 'is_pharmacy')) {
                $table->dropColumn('is_pharmacy');
            }
        });
    }
};
