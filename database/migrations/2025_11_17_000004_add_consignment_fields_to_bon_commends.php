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
        Schema::table('bon_commends', function (Blueprint $table) {
            // Check before adding to make migration idempotent
            if (! Schema::hasColumn('bon_commends', 'is_from_consignment')) {
                $table->boolean('is_from_consignment')
                    ->default(false)
                    ->after('is_confirmed');
            }

            if (! Schema::hasColumn('bon_commends', 'consignment_source_id')) {
                $table->unsignedBigInteger('consignment_source_id')
                    ->nullable()
                    ->after('is_from_consignment');

                $table->foreign('consignment_source_id')
                    ->references('id')
                    ->on('consignment_receptions')
                    ->onDelete('set null');

                $table->index('consignment_source_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_commends', function (Blueprint $table) {
            if (Schema::hasColumn('bon_commends', 'consignment_source_id')) {
                $table->dropForeign(['consignment_source_id']);
                $table->dropIndex(['consignment_source_id']);
                $table->dropColumn('consignment_source_id');
            }

            if (Schema::hasColumn('bon_commends', 'is_from_consignment')) {
                $table->dropColumn('is_from_consignment');
            }
        });
    }
};
