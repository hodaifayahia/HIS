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
        Schema::create('bon_entree_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_entree_id')->constrained('bon_entrees')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('in_stock_id')->nullable()->constrained('stock')->onDelete('set null');
            $table->string('storage_name')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('boite_de')->default(1);
            $table->integer('quantity');
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('sell_price', 10, 2)->nullable();
            $table->decimal('tva', 5, 2)->nullable();
            $table->boolean('by_box')->default(false);
            $table->integer('qte_by_box')->default(1);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['bon_entree_id', 'product_id']);
            $table->index('product_id');
            $table->index('batch_number');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_entree_items');
    }
};
