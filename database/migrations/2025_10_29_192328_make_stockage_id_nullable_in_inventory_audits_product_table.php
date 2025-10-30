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
        Schema::table('inventory_audits_product', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['stockage_id']);
            
            // Change the column to nullable
            $table->unsignedBigInteger('stockage_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable
            $table->foreign('stockage_id')
                  ->references('id')
                  ->on('stockages')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_audits_product', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['stockage_id']);
            
            // Change the column back to not nullable
            $table->unsignedBigInteger('stockage_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('stockage_id')
                  ->references('id')
                  ->on('stockages')
                  ->onDelete('cascade');
        });
    }
};
