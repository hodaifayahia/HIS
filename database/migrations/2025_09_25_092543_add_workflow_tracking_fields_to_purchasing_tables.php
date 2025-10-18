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
        // Add workflow tracking fields to factureproforma_products
        Schema::table('factureproforma_products', function (Blueprint $table) {
            $table->integer('quantity_sended')->default(0)->after('quantity');
            $table->string('confirmation_status')->default('pending')->after('quantity_sended');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_status');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
        });

        // Add workflow tracking fields to bon_commend_items
        Schema::table('bon_commend_items', function (Blueprint $table) {
            $table->integer('quantity_sended')->default(0)->after('quantity_desired');
            $table->string('source_type')->default('service_demand')->after('quantity_sended'); // 'service_demand' or 'facture_proforma'
            $table->foreignId('source_id')->nullable()->after('source_type'); // points to service_demand or facture_proforma
            $table->timestamp('confirmed_at')->nullable()->after('source_id');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
        });

        // Add confirmation tracking to service_demand_purchasings
        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            $table->boolean('proforma_confirmed')->default(false)->after('status');
            $table->boolean('boncommend_confirmed')->default(false)->after('proforma_confirmed');
            $table->timestamp('proforma_confirmed_at')->nullable()->after('boncommend_confirmed');
            $table->timestamp('boncommend_confirmed_at')->nullable()->after('proforma_confirmed_at');
        });

        // Add confirmation tracking to factureproformas
        Schema::table('factureproformas', function (Blueprint $table) {
            $table->boolean('is_confirmed')->default(false)->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('is_confirmed');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
        });

        // Add confirmation tracking to bon_commends
        Schema::table('bon_commends', function (Blueprint $table) {
            $table->boolean('is_confirmed')->default(false)->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('is_confirmed');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->after('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factureproforma_products', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['quantity_sended', 'confirmation_status', 'confirmed_at', 'confirmed_by']);
        });

        Schema::table('bon_commend_items', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['quantity_sended', 'source_type', 'source_id', 'confirmed_at', 'confirmed_by']);
        });

        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            $table->dropColumn(['proforma_confirmed', 'boncommend_confirmed', 'proforma_confirmed_at', 'boncommend_confirmed_at']);
        });

        Schema::table('factureproformas', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['is_confirmed', 'confirmed_at', 'confirmed_by']);
        });

        Schema::table('bon_commends', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['is_confirmed', 'confirmed_at', 'confirmed_by']);
        });
    }
};
