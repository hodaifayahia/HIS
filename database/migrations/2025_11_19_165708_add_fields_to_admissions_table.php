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
            $table->string('file_number')->unique()->nullable()->after('id');
            $table->boolean('file_number_verified')->default(false)->after('file_number');
            $table->text('observation')->nullable()->after('documents_verified');
            $table->foreignId('company_id')->nullable()->constrained('organismes')->nullOnDelete()->after('doctor_id');
            $table->string('social_security_num')->nullable()->after('observation');
            $table->string('relation_type')->nullable()->after('companion_id');
            
            $table->index('file_number_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['file_number_verified']);
            $table->dropColumn([
                'file_number',
                'file_number_verified',
                'observation',
                'company_id',
                'social_security_num',
                'relation_type',
            ]);
        });
    }
};
