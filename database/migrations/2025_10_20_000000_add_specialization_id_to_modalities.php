<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modalities', function (Blueprint $table) {
            if (!Schema::hasColumn('modalities', 'specialization_id')) {
                $table->unsignedBigInteger('specialization_id')->nullable()->after('operational_status');
                $table->index('specialization_id', 'modalities_specialization_id_index');
                $table->foreign('specialization_id', 'modalities_specialization_id_foreign')
                      ->references('id')->on('specializations')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modalities', function (Blueprint $table) {
            if (Schema::hasColumn('modalities', 'specialization_id')) {
                $table->dropForeign('modalities_specialization_id_foreign');
                $table->dropIndex('modalities_specialization_id_index');
                $table->dropColumn('specialization_id');
            }
        });
    }
};
