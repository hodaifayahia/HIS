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
        Schema::create('service_demand_item_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_demand_purchasing_item_id')
                ->constrained('service_demand_purchasing_items')
                ->onDelete('cascade')
                ->name('sd_item_fournisseur_item_foreign');
            $table->foreignId('fournisseur_id')
                ->constrained('fournisseurs')
                ->onDelete('cascade')
                ->name('sd_item_fournisseur_fournisseur_foreign');
            $table->integer('assigned_quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, ordered, received
            $table->foreignId('assigned_by')
                ->constrained('users')
                ->name('sd_item_fournisseur_assigned_by_foreign');
            $table->timestamps();

            // Ensure a fournisseur can only be assigned once per item
            $table->unique(['service_demand_purchasing_item_id', 'fournisseur_id'], 'sd_item_fournisseur_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_demand_item_fournisseurs');
    }
};
