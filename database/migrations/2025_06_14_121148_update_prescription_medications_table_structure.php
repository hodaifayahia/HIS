<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// Create new migration: php artisan make:migration update_prescription_medications_table_structure

public function up()
{
    Schema::table('prescription_medications', function (Blueprint $table) {
      

        // Add or modify columns
        $table->string('form');
        $table->string('num_times');
        $table->string('frequency-period');
        $table->integer('period_intakes');
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->text('description')->nullable();

        // Add foreign key
        // $table->foreign('medication_id')
        //       ->references('id')
        //       ->on('medications')
        //       ->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('prescription_medications', function (Blueprint $table) {
        $table->dropForeign(['medication_id']);
        $table->dropColumn([
            'medication_id',
            'form',
            'num_times',
            'frequency',
            'start_date',
            'end_date',
            'description'
        ]);

        // Restore old columns
        $table->string('cd_active_substance');
        $table->string('brand_name')->nullable();
        $table->string('pharmaceutical_form');
        $table->string('dose_per_intake')->nullable();
        $table->string('duration_or_boxes')->nullable();
    });
}
};
