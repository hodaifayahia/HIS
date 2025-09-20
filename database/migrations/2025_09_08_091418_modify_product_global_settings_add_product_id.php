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
        Schema::table('product_global_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->after('id');
            $table->string('setting_key');
            $table->json('setting_value');
            $table->text('description')->nullable();
            
            // Add foreign key constraint
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            // Add unique constraint for product_id + setting_key combination
            $table->unique(['product_id', 'setting_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_global_settings', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropUnique(['product_id', 'setting_key']);
            $table->dropColumn(['product_id', 'setting_key', 'setting_value', 'description']);
        });
    }
};
