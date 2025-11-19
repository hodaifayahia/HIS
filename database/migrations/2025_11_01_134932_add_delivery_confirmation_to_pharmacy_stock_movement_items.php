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
        Schema::table('pharmacy_stock_movement_items', function (Blueprint $table) {
            // Add delivery confirmation columns only if they don't exist
            if (!Schema::hasColumn('pharmacy_stock_movement_items', 'confirmation_status')) {
                $table->enum('confirmation_status', ['good', 'damaged', 'manque'])->nullable()->after('status');
            }
            if (!Schema::hasColumn('pharmacy_stock_movement_items', 'confirmation_notes')) {
                $table->text('confirmation_notes')->nullable()->after('confirmation_status');
            }
            if (!Schema::hasColumn('pharmacy_stock_movement_items', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmation_notes');
            }
            if (!Schema::hasColumn('pharmacy_stock_movement_items', 'confirmed_by')) {
                $table->bigInteger('confirmed_by')->unsigned()->nullable()->after('confirmed_at');
                
                // Add foreign key for confirmed_by
                $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('pharmacy_stock_movement_items', 'received_quantity')) {
                $table->decimal('received_quantity', 10, 2)->nullable()->after('confirmed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_stock_movement_items', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['confirmed_by']);
            
            // Drop the columns
            $table->dropColumn([
                'confirmation_status',
                'confirmation_notes',
                'confirmed_at',
                'confirmed_by',
                'received_quantity'
            ]);
        });
    }
};
