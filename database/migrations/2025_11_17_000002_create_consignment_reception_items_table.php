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
        Schema::create('consignment_reception_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consignment_reception_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_consumed')->default(0);
            $table->integer('quantity_invoiced')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('consignment_reception_id', 'consignment_reception_items_reception_fk')
                ->references('id')
                ->on('consignment_receptions')
                ->onDelete('cascade'); // Delete items when reception deleted

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict'); // Prevent product deletion if used in consignment

            // Unique constraint: one product per consignment reception
            $table->unique(['consignment_reception_id', 'product_id'], 'consignment_items_unique');

            // Indexes for performance
            $table->index('consignment_reception_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_reception_items');
    }
};
