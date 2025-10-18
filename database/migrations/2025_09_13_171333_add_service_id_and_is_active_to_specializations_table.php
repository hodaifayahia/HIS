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
        Schema::table('specializations', function (Blueprint $table) {
            if (! Schema::hasColumn('specializations', 'service_id')) {
                $table->unsignedBigInteger('service_id')->default(1)->after('id');
            }
            if (! Schema::hasColumn('specializations', 'is_active')) {
                $table->tinyInteger('is_active')->default(1)->after('service_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('specializations', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'is_active']);
        });
    }
};
