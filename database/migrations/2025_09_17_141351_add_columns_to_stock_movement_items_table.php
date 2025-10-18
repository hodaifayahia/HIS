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
        Schema::table('stock_movement_items', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('stock_movement_items', 'stock_movement_id')) {
                $table->unsignedBigInteger('stock_movement_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'requested_quantity')) {
                $table->decimal('requested_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'approved_quantity')) {
                $table->decimal('approved_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'executed_quantity')) {
                $table->decimal('executed_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'provided_quantity')) {
                $table->decimal('provided_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('stock_movement_items', 'quantity_by_box')) {
                $table->decimal('quantity_by_box', 10, 2)->nullable();
            }
            
            // Add foreign key constraints
            try {
                $table->foreign('stock_movement_id')->references('id')->on('stock_movements')->onDelete('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
            
            try {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movement_items', function (Blueprint $table) {
            // Drop foreign keys first
            try {
                $table->dropForeign(['stock_movement_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            try {
                $table->dropForeign(['product_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Drop columns only if they exist
            $columnsToCheck = [
                'quantity_by_box',
                'notes',
                'provided_quantity',
                'executed_quantity',
                'approved_quantity',
                'requested_quantity',
                'product_id',
                'stock_movement_id'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('stock_movement_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
