<?php
// database/migrations/create_refund_authorizations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_navette_item_id')->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('item_dependency_id')->nullable()->constrained('item_dependencies')->onDelete('cascade');
            $table->foreignId('requested_by_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('authorized_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('reason');
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('authorized_amount', 15, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'used'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['fiche_navette_item_id', 'status']);
            $table->index(['requested_by_id', 'status']);
            $table->index(['authorized_by_id', 'status']);
            $table->index('expires_at');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_authorizations');
    }
};
