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
        Schema::table('pharmacy_products', function (Blueprint $table) {
            // Add only the missing fields that don't exist yet
            // Check first if the column exists before adding
            if (! Schema::hasColumn('pharmacy_products', 'quantity_by_box')) {
                $table->boolean('quantity_by_box')->default(0);
            }
            if (! Schema::hasColumn('pharmacy_products', 'nom_commercial')) {
                $table->string('nom_commercial')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_products', 'status')) {
                $table->enum('status', ['In Stock', 'Low Stock', 'Out of Stock'])->default('In Stock');
            }
            if (! Schema::hasColumn('pharmacy_products', 'is_request_approval')) {
                $table->boolean('is_request_approval')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn([
                'quantity_by_box',
                'nom_commercial',
                'status',
                'is_request_approval',
            ]);
        });
    }
};
