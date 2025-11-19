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
        if (!Schema::hasTable('inventory_audits_product')) {
            Schema::create('inventory_audits_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_audit_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('stockage_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('participant_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->decimal('theoretical_quantity', 10, 2);
                $table->decimal('actual_quantity', 10, 2)->nullable();
                $table->decimal('difference', 10, 2)->nullable();
                $table->decimal('variance_percent', 8, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('audited_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('audited_at');
                $table->enum('status', ['pending', 'completed', 'approved', 'rejected'])->default('completed');
                $table->timestamps();
                
                // Indexes for better query performance
                $table->index(['product_id', 'stockage_id', 'audited_at']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_audits');
    }
};
