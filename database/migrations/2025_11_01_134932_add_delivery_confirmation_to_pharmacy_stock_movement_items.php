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
            // Add delivery confirmation columns
            $table->enum('confirmation_status', ['good', 'damaged', 'manque'])->nullable()->after('status');
            $table->text('confirmation_notes')->nullable()->after('confirmation_status');
            $table->timestamp('confirmed_at')->nullable()->after('confirmation_notes');
            $table->bigInteger('confirmed_by')->unsigned()->nullable()->after('confirmed_at');
            $table->decimal('received_quantity', 10, 2)->nullable()->after('confirmed_by');
            
            // Add foreign key for confirmed_by
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
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
