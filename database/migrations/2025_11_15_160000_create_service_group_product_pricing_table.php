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
        Schema::create('service_group_product_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_group_id')->constrained('service_groups')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('pharmacy_product_id')->nullable()->constrained('pharmacy_products')->onDelete('cascade');
            $table->boolean('is_pharmacy')->default(false)->comment('Distinguish between pharmacy and stock products');
            $table->decimal('selling_price', 10, 2)->comment('Price for selling this product in this service group');
            $table->decimal('purchase_price', 10, 2)->nullable()->comment('Reference purchase price for cost tracking');
            $table->decimal('vat_rate', 5, 2)->default(0.00)->comment('VAT percentage (e.g., 19.00 for 19%)');
            $table->timestamp('effective_from')->useCurrent()->comment('When this pricing becomes effective');
            $table->timestamp('effective_to')->nullable()->comment('When this pricing expires (NULL = current)');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable()->comment('Reason for price change or other notes');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index('service_group_id');
            $table->index('product_id');
            $table->index('pharmacy_product_id');
            $table->index('effective_from');
            $table->index('effective_to');
            $table->index(['service_group_id', 'product_id', 'is_pharmacy', 'effective_to'], 'sgp_current_pricing_idx');

            // Unique constraint: only one active pricing per service group + product + type
            $table->unique(
                ['service_group_id', 'product_id', 'pharmacy_product_id', 'is_pharmacy', 'effective_to'],
                'sgp_unique_active_pricing'
            );
        });

        // Add service_group_id to bon_entrees if not exists
        if (Schema::hasTable('bon_entrees') && ! Schema::hasColumn('bon_entrees', 'service_group_id')) {
            Schema::table('bon_entrees', function (Blueprint $table) {
                $table->foreignId('service_group_id')->nullable()->after('fournisseur_id')->constrained('service_groups')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove service_group_id from bon_entrees if exists
        if (Schema::hasTable('bon_entrees') && Schema::hasColumn('bon_entrees', 'service_group_id')) {
            Schema::table('bon_entrees', function (Blueprint $table) {
                $table->dropForeign(['service_group_id']);
                $table->dropColumn('service_group_id');
            });
        }

        Schema::dropIfExists('service_group_product_pricing');
    }
};
