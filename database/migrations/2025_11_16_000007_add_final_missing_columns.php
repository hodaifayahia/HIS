<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add all remaining missing columns found in models
     */
    public function up(): void
    {
        // Attributes table
        Schema::table('attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('attributes', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable();
            }
            if (! Schema::hasColumn('attributes', 'is_required')) {
                $table->boolean('is_required')->default(false);
            }
        });

        // Bon Entrees table
        Schema::table('bon_entrees', function (Blueprint $table) {
            if (! Schema::hasColumn('bon_entrees', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable();
            }
        });

        // Doctors table
        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'days')) {
                $table->string('days')->nullable();
            }
            if (! Schema::hasColumn('doctors', 'number_of_patients_per_day')) {
                $table->integer('number_of_patients_per_day')->nullable();
            }
        });

        // Folders table
        Schema::table('folders', function (Blueprint $table) {
            // Already added in previous migrations
        });

        // Pharmacy Products table
        Schema::table('pharmacy_products', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_products', 'code_interne')) {
                $table->string('code_interne')->nullable();
            }
        });

        // Products table
        Schema::table('products', function (Blueprint $table) {
            // is_required_approval vs is_request_approval - naming inconsistency already exists
        });

        // Services table
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (! Schema::hasColumn('services', 'end_date')) {
                $table->date('end_date')->nullable();
            }
        });

        // Service Demand Purchasing
        Schema::table('service_demand_purchasings', function (Blueprint $table) {
            // is_pharmacy_order already added or is_pharmacy field exists
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn(['doctor_id', 'is_required']);
        });

        Schema::table('bon_entrees', function (Blueprint $table) {
            $table->dropColumn('service_id');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['days', 'number_of_patients_per_day']);
        });

        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn('code_interne');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
