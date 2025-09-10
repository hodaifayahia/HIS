<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->integer('age')->nullable();
            $table->decimal('weight', 5, 2)->nullable()->after('age'); // 5 digits total, 2 after decimal
        });
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['age', 'weight']);
        });
    }
};