<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if the schedules table doesn't exist yet
        if (!Schema::hasTable('schedules')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            // Add soft deletes if not already present
            if (!Schema::hasColumn('schedules', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Add break duration column if not present
            if (!Schema::hasColumn('schedules', 'break_duration')) {
                $table->integer('break_duration')->nullable()->comment('Break duration in minutes');
            }
            
            // Add break times column if not present
            if (!Schema::hasColumn('schedules', 'break_times')) {
                $table->json('break_times')->nullable()->comment('JSON array of break time slots');
            }
            
            // Add excluded dates column if not present
            if (!Schema::hasColumn('schedules', 'excluded_dates')) {
                $table->json('excluded_dates')->nullable()->comment('JSON array of excluded dates');
            }
            
            // Add modified times column if not present
            if (!Schema::hasColumn('schedules', 'modified_times')) {
                $table->json('modified_times')->nullable()->comment('JSON array of modified times for specific dates');
            }

            // Check if indexes exist before creating them
            $indexExists = DB::select("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'schedules' AND index_name = 'idx_doctor_active'");
            if (empty($indexExists)) {
                $table->index(['doctor_id', 'is_active'], 'idx_doctor_active');
            }
            
            $indexExists = DB::select("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'schedules' AND index_name = 'idx_day_shift'");
            if (empty($indexExists)) {
                $table->index(['day_of_week', 'shift_period'], 'idx_day_shift');
            }
            
            $indexExists = DB::select("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'schedules' AND index_name = 'idx_schedule_date'");
            if (empty($indexExists)) {
                $table->index(['date'], 'idx_schedule_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_doctor_active');
            $table->dropIndex('idx_day_shift');
            $table->dropIndex('idx_schedule_date');
            
            // Drop columns if they exist
            if (Schema::hasColumn('schedules', 'modified_times')) {
                $table->dropColumn('modified_times');
            }
            
            if (Schema::hasColumn('schedules', 'excluded_dates')) {
                $table->dropColumn('excluded_dates');
            }
            
            if (Schema::hasColumn('schedules', 'break_times')) {
                $table->dropColumn('break_times');
            }
            
            if (Schema::hasColumn('schedules', 'break_duration')) {
                $table->dropColumn('break_duration');
            }
            
            if (Schema::hasColumn('schedules', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};