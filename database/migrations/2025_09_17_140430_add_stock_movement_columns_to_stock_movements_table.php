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
        Schema::table('stock_movements', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('stock_movements', 'movement_number')) {
                $table->string('movement_number')->unique()->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'requesting_service_id')) {
                $table->unsignedBigInteger('requesting_service_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'providing_service_id')) {
                $table->unsignedBigInteger('providing_service_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'requesting_user_id')) {
                $table->unsignedBigInteger('requesting_user_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'approving_user_id')) {
                $table->unsignedBigInteger('approving_user_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'executing_user_id')) {
                $table->unsignedBigInteger('executing_user_id')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'requested_quantity')) {
                $table->decimal('requested_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'approved_quantity')) {
                $table->decimal('approved_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'executed_quantity')) {
                $table->decimal('executed_quantity', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'status')) {
                $table->string('status')->default('draft');
            }
            if (!Schema::hasColumn('stock_movements', 'request_reason')) {
                $table->text('request_reason')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'approval_notes')) {
                $table->text('approval_notes')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'execution_notes')) {
                $table->text('execution_notes')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'requested_at')) {
                $table->timestamp('requested_at')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'executed_at')) {
                $table->timestamp('executed_at')->nullable();
            }
            if (!Schema::hasColumn('stock_movements', 'expected_delivery_date')) {
                $table->timestamp('expected_delivery_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Drop columns only if they exist
            $columnsToCheck = [
                'expected_delivery_date',
                'executed_at',
                'approved_at',
                'requested_at',
                'execution_notes',
                'approval_notes',
                'request_reason',
                'status',
                'executed_quantity',
                'approved_quantity',
                'requested_quantity',
                'executing_user_id',
                'approving_user_id',
                'requesting_user_id',
                'providing_service_id',
                'requesting_service_id',
                'product_id',
                'movement_number'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('stock_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
