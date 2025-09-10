
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('placeholder_templates', function (Blueprint $table) {
            $table->foreignId('attribute_id')
                  ->nullable()
                  ->after('placeholder_id')
                  ->constrained()
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('placeholder_templates', function (Blueprint $table) {
            $table->dropForeign(['attribute_id']);
            $table->dropColumn('attribute_id');
        });
    }
};