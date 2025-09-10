<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->after('patient_id');
            $table->date('start_date')->nullable()->after('doctor_id');
            $table->date('end_date')->nullable()->after('start_date');
            // Ensure that the doctor_id can be null, as not all prescriptions may have a doctor associated
            $table->string('pdf_path')->nullable()->after('signature_status');

        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropColumn('doctor_id');
        });
    }
};