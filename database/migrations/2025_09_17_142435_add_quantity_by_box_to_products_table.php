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
        Schema::table('products', function (Blueprint $table) {
            // Add quantity_by_box column if it doesn't exist
            if (! Schema::hasColumn('products', 'quantity_by_box')) {
                $table->boolean('quantity_by_box')->default(false)->after('boite_de');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop quantity_by_box column if it exists
            if (Schema::hasColumn('products', 'quantity_by_box')) {
                $table->dropColumn('quantity_by_box');
            }
        });
    }
};
