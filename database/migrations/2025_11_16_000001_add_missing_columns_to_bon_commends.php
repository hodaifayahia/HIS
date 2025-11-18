<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_commends', function (Blueprint $table) {
            // Add missing critical columns
            if (! Schema::hasColumn('bon_commends', 'bonCommendCode')) {
                $table->string('bonCommendCode')->unique()->after('id');
            }
            if (! Schema::hasColumn('bon_commends', 'fournisseur_id')) {
                $table->unsignedBigInteger('fournisseur_id')->nullable()->after('bonCommendCode');
            }
            if (! Schema::hasColumn('bon_commends', 'service_demand_purchasing_id')) {
                $table->unsignedBigInteger('service_demand_purchasing_id')->nullable()->after('fournisseur_id');
            }
            if (! Schema::hasColumn('bon_commends', 'order_date')) {
                $table->date('order_date')->nullable()->after('service_demand_purchasing_id');
            }
            if (! Schema::hasColumn('bon_commends', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('order_date');
            }
            if (! Schema::hasColumn('bon_commends', 'department')) {
                $table->string('department')->nullable()->after('expected_delivery_date');
            }
            if (! Schema::hasColumn('bon_commends', 'priority')) {
                $table->string('priority')->default('normal')->after('department');
            }
            if (! Schema::hasColumn('bon_commends', 'notes')) {
                $table->text('notes')->nullable()->after('priority');
            }
            if (! Schema::hasColumn('bon_commends', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('bon_commends', 'status')) {
                $table->string('status')->default('draft')->after('created_by');
            }
            if (! Schema::hasColumn('bon_commends', 'boncommend_confirmed_at')) {
                $table->timestamp('boncommend_confirmed_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bon_commends', function (Blueprint $table) {
            $table->dropColumn([
                'bonCommendCode',
                'fournisseur_id',
                'service_demand_purchasing_id',
                'order_date',
                'expected_delivery_date',
                'department',
                'priority',
                'notes',
                'created_by',
                'status',
                'boncommend_confirmed_at',
            ]);
        });
    }
};
