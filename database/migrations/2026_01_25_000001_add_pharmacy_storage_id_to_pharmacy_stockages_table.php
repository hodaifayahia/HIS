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
        Schema::table('pharmacy_stockages', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_stockages', 'pharmacy_storage_id')) {
                $table->unsignedBigInteger('pharmacy_storage_id')->nullable()->after('service_id');
                $table->foreign('pharmacy_storage_id')->references('id')->on('pharmacy_storages')->onDelete('set null');
                $table->index('pharmacy_storage_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_stockages', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_storage_id']);
            $table->dropIndex(['pharmacy_storage_id']);
            $table->dropColumn('pharmacy_storage_id');
        });
    }
};
