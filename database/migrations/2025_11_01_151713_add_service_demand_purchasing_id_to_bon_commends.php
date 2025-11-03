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
        Schema::table('bon_commends', function (Blueprint $table) {
            // Add service_demand_purchasing_id if it doesn't exist
            if (!Schema::hasColumn('bon_commends', 'service_demand_purchasing_id')) {
                $table->unsignedBigInteger('service_demand_purchasing_id')->nullable()->after('id');
                $table->foreign('service_demand_purchasing_id')
                    ->references('id')
                    ->on('service_demand_purchasings')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_commends', function (Blueprint $table) {
            if (Schema::hasColumn('bon_commends', 'service_demand_purchasing_id')) {
                $table->dropForeign(['service_demand_purchasing_id']);
                $table->dropColumn('service_demand_purchasing_id');
            }
        });
    }
};
