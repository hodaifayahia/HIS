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
        Schema::create('inventory_audit_recounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_audit_id')->constrained('inventory_audits')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_type')->default('stock');
            $table->boolean('is_recount_mode')->default(true);
            $table->boolean('show_other_counts')->default(false);
            $table->decimal('original_quantity', 15, 2)->nullable();
            $table->decimal('recount_quantity', 15, 2)->nullable();
            $table->timestamp('recounted_at')->nullable();
            $table->timestamps();

            // Unique constraint: one recount record per audit/participant/product
            $table->unique(['inventory_audit_id', 'participant_id', 'product_id', 'product_type'], 'unique_recount_record');
            
            // Indexes for performance
            $table->index(['inventory_audit_id', 'participant_id']);
            $table->index(['inventory_audit_id', 'product_id']);
        });

        // Add recount tracking fields to participants table
        Schema::table('inventory_audits_participantes', function (Blueprint $table) {
            $table->boolean('is_in_recount_mode')->default(false)->after('status');
            $table->integer('recount_products_count')->default(0)->after('is_in_recount_mode');
        });

        // Add original quantity tracking to product counts
        Schema::table('inventory_audits_product', function (Blueprint $table) {
            $table->decimal('original_quantity', 15, 2)->nullable()->after('actual_quantity');
            $table->boolean('is_recount')->default(false)->after('original_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_audits_product', function (Blueprint $table) {
            $table->dropColumn(['original_quantity', 'is_recount']);
        });

        Schema::table('inventory_audits_participantes', function (Blueprint $table) {
            $table->dropColumn(['is_in_recount_mode', 'recount_products_count']);
        });

        Schema::dropIfExists('inventory_audit_recounts');
    }
};
