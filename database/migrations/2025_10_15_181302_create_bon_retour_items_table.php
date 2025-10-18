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
        Schema::create('bon_retour_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bon_retour_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('bon_entree_item_id')->nullable()->comment('Reference to original item');
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity_returned');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tva', 5, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('return_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->string('storage_location')->nullable();
            $table->boolean('stock_updated')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('bon_retour_id');
            $table->index('product_id');
            $table->index('batch_number');
            $table->index(['bon_retour_id', 'product_id']);

            // Foreign keys
            $table->foreign('bon_retour_id')
                ->references('id')
                ->on('bon_retours')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');

            $table->foreign('bon_entree_item_id')
                ->references('id')
                ->on('bon_entree_items')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_retour_items');
    }
};
