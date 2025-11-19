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
            // Add companion_id foreign key
            $table->foreignId('companion_id')->nullable()->after('doctor_id')->constrained('patients')->onDelete('set null');
            
            // Add index for performance
            $table->index('companion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeignIdFor('patients', 'companion_id');
            $table->dropIndex(['companion_id']);
        });
    }
};
