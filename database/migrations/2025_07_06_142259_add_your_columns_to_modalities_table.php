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
        // Add columns to existing 'modalities' table
        Schema::table('modalities', function (Blueprint $table) {
            // For Service/Department
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');

            // For Integration Protocol (for lab analyzers)
            $table->string('integration_protocol')->nullable();

            // For Connection Configuration (for lab analyzers)
            $table->text('connection_configuration')->nullable();

            // For Data Retrieval Method (for portable devices)
            $table->string('data_retrieval_method')->nullable();

            // For IP Address (for DICOM equipment)
            $table->string('ip_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            if (Schema::hasColumn('modalities', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            }

            if (Schema::hasColumn('modalities', 'integration_protocol')) {
                $table->dropColumn('integration_protocol');
            }

            if (Schema::hasColumn('modalities', 'connection_configuration')) {
                $table->dropColumn('connection_configuration');
            }

            if (Schema::hasColumn('modalities', 'data_retrieval_method')) {
                $table->dropColumn('data_retrieval_method');
            }

            if (Schema::hasColumn('modalities', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
        });
    }
};