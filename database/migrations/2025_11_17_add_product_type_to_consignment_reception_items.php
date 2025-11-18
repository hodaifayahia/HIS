<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignment_reception_items', function (Blueprint $table) {
            // Add product_type column if it doesn't exist
            if (! Schema::hasColumn('consignment_reception_items', 'product_type')) {
                $table->string('product_type')->default('stock')->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consignment_reception_items', function (Blueprint $table) {
            if (Schema::hasColumn('consignment_reception_items', 'product_type')) {
                $table->dropColumn('product_type');
            }
        });
    }
};
