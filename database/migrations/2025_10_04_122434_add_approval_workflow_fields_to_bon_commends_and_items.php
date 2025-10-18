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
        // Add approval workflow fields to bon_commends table
        Schema::table('bon_commends', function (Blueprint $table) {
            if (! Schema::hasColumn('bon_commends', 'approval_status')) {
                $table->string('approval_status', 20)->default('draft')->after('status');
            }
            if (! Schema::hasColumn('bon_commends', 'has_approver_modifications')) {
                $table->boolean('has_approver_modifications')->default(false)->after('approval_status');
            }
        });

        // Add approval workflow fields to bon_commend_items table
        Schema::table('bon_commend_items', function (Blueprint $table) {
            if (! Schema::hasColumn('bon_commend_items', 'modified_by_approver')) {
                $table->boolean('modified_by_approver')->default(false)->after('quantity_desired');
            }
            if (! Schema::hasColumn('bon_commend_items', 'original_quantity_desired')) {
                $table->integer('original_quantity_desired')->nullable()->after('modified_by_approver');
            }
        });

        // Add approval workflow field to products table
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_request_approval')) {
                $table->boolean('is_request_approval')->default(false)->after('is_required_approval');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove approval workflow fields from bon_commends table
        Schema::table('bon_commends', function (Blueprint $table) {
            if (Schema::hasColumn('bon_commends', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('bon_commends', 'has_approver_modifications')) {
                $table->dropColumn('has_approver_modifications');
            }
        });

        // Remove approval workflow fields from bon_commend_items table
        Schema::table('bon_commend_items', function (Blueprint $table) {
            if (Schema::hasColumn('bon_commend_items', 'modified_by_approver')) {
                $table->dropColumn('modified_by_approver');
            }
            if (Schema::hasColumn('bon_commend_items', 'original_quantity_desired')) {
                $table->dropColumn('original_quantity_desired');
            }
        });

        // Remove approval workflow field from products table
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_request_approval')) {
                $table->dropColumn('is_request_approval');
            }
        });
    }
};
