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
        Schema::create('pharmacy_movement_inventory_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pharmacy_stock_movement_item_id');
            $table->unsignedBigInteger('pharmacy_inventory_id');
            $table->decimal('selected_quantity', 10, 2);
            $table->timestamps();

            // Foreign key constraints with shorter names
            $table->foreign('pharmacy_stock_movement_item_id', 'psmis_item_fk')->references('id')->on('pharmacy_stock_movement_items')->onDelete('cascade');
            $table->foreign('pharmacy_inventory_id', 'psmis_inventory_fk')->references('id')->on('pharmacy_inventories')->onDelete('cascade');

            // Ensure unique selection per inventory item
            $table->unique(['pharmacy_stock_movement_item_id', 'pharmacy_inventory_id'], 'unique_pharmacy_inventory_selection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_movement_inventory_selections');
    }
};
