<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToPatientsTable extends Migration
{
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('firstname_ar')->nullable();
            $table->string('lastname_ar')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('nss')->nullable(); // Social security number
            $table->string('marital_status')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->string('mother_lastname')->nullable();
            $table->string('mother_firstname')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('blood_group')->nullable();
            $table->text('other_clinical_info')->nullable();
        });
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'firstname_ar',
                'lastname_ar',
                'birth_place',
                'nss',
                'marital_status',
                'height',
                'mother_lastname',
                'mother_firstname',
                'email',
                'address',
                'postal_code',
                'city',
                'blood_group',
                'other_clinical_info',
            ]);
        });
    }
}
