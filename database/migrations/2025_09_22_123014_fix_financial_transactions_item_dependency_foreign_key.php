<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, clean up invalid item_dependency_id values
        DB::table('financial_transactions')
            ->whereNotNull('item_dependency_id')
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('item_dependencies')
                      ->whereRaw('item_dependencies.id = financial_transactions.item_dependency_id');
            })
            ->update(['item_dependency_id' => null]);

        Schema::table('financial_transactions', function (Blueprint $table) {
            // Change item_dependency_id from int to bigint unsigned to match item_dependencies.id
            $table->bigInteger('item_dependency_id')->nullable()->unsigned()->change();
            
            // Add the foreign key constraint
            $table->foreign('item_dependency_id')->references('id')->on('item_dependencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['item_dependency_id']);
            
            // Change back to int (though this might not be necessary for rollback)
            $table->integer('item_dependency_id')->nullable()->change();
        });
    }
};
