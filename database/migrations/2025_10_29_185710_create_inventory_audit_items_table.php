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
        if (!Schema::hasTable('inventory_audit_items')) {
            Schema::create('inventory_audit_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_audit_id')->constrained('inventory_audits')->onDelete('cascade');
                $table->unsignedBigInteger('product_id');
                $table->string('product_type'); // 'pharmacy' or 'stock'
                $table->unsignedBigInteger('stockage_id')->nullable();
                $table->decimal('theoretical_quantity', 10, 2)->default(0);
                $table->decimal('actual_quantity', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index(['inventory_audit_id', 'product_id', 'product_type']);
                $table->index('stockage_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_items');
    }
};
