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
        Schema::create('stock_movement_inventory_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_movement_item_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('selected_quantity', 10, 2);
            $table->timestamps();

            // Foreign key constraints with shorter names
            $table->foreign('stock_movement_item_id', 'smis_item_fk')->references('id')->on('stock_movement_items')->onDelete('cascade');
            $table->foreign('inventory_id', 'smis_inventory_fk')->references('id')->on('inventories')->onDelete('cascade');

            // Ensure unique selection per inventory item
            $table->unique(['stock_movement_item_id', 'inventory_id'], 'unique_inventory_selection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movement_inventory_selections');
    }
};
