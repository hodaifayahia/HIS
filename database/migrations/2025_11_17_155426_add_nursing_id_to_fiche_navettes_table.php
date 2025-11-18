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
        Schema::table('fiche_navettes', function (Blueprint $table) {
            if (! Schema::hasColumn('fiche_navettes', 'nursing_id')) {
                $table->foreignId('nursing_id')
                    ->nullable()
                    ->after('emergency_doctor_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navettes', function (Blueprint $table) {
            if (Schema::hasColumn('fiche_navettes', 'nursing_id')) {
                $table->dropForeign(['nursing_id']);
                $table->dropColumn('nursing_id');
            }
        });
    }
};
