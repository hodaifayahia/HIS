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
        Schema::create('bon_reception_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('bon_reception_id')->constrained('bon_receptions')->onDelete('cascade');
            $table->foreignId('bon_commend_item_id')->nullable()->constrained('bon_commend_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Quantities
            $table->integer('quantity_ordered'); // What was originally ordered
            $table->integer('quantity_received'); // What actually arrived
            $table->integer('quantity_surplus')->default(0); // Extra quantity received
            $table->integer('quantity_shortage')->default(0); // Missing quantity

            // Product details (copied from order for reference)
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();

            // Status and notes
            $table->enum('status', ['pending', 'received', 'partial', 'excess', 'missing'])->default('pending');
            $table->text('notes')->nullable();

            // Tracking
            $table->boolean('is_unexpected')->default(false); // For products not in original order
            $table->datetime('received_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['bon_reception_id', 'status']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_reception_items');
    }
};
