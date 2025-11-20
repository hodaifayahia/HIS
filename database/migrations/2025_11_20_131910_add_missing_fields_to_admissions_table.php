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
        Schema::table('admissions', function (Blueprint $table) {
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('admissions', 'file_number')) {
                $table->string('file_number')->unique()->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('admissions', 'file_number_verified')) {
                $table->boolean('file_number_verified')->default(false)->after('file_number');
            }
            if (!Schema::hasColumn('admissions', 'observation')) {
                $table->text('observation')->nullable()->after('file_number_verified');
            }
            if (!Schema::hasColumn('admissions', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('organismes')->onDelete('set null')->after('observation');
            }
            if (!Schema::hasColumn('admissions', 'social_security_num')) {
                $table->string('social_security_num')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('admissions', 'relation_type')) {
                $table->string('relation_type')->nullable()->after('social_security_num');
            }
            if (!Schema::hasColumn('admissions', 'reason_for_admission')) {
                $table->text('reason_for_admission')->nullable()->after('relation_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumnIfExists([
                'file_number',
                'file_number_verified',
                'observation',
                'company_id',
                'social_security_num',
                'relation_type',
                'reason_for_admission',
            ]);
        });
    }
};
