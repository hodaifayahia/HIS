<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Drop the incorrect FK and add the correct one to `specializations`.
     *
     * @return void
     */
    public function up()
    {
        // Defensive: drop the known incorrect foreign key if it exists
        // The user error referenced `doctor_salls_doctor_id_foreign` as the failing constraint
        try {
            DB::statement('ALTER TABLE `specializations_salls` DROP FOREIGN KEY `doctor_salls_doctor_id_foreign`');
        } catch (\Exception $e) {
            // ignore if it doesn't exist
            \Log::info('FK doctor_salls_doctor_id_foreign not dropped (may not exist): ' . $e->getMessage());
        }

        // Also attempt to drop any FK on the specialization_id column (generic)
        try {
            Schema::table('specializations_salls', function (Blueprint $table) {
                $table->dropForeign(['specialization_id']);
            });
        } catch (\Exception $e) {
            // ignore
        }

        // Add the correct foreign key to specializations(id)
        try {
            DB::statement('ALTER TABLE `specializations_salls` ADD CONSTRAINT `fk_specializations_salls_specialization_id` FOREIGN KEY (`specialization_id`) REFERENCES `specializations`(`id`) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // bubble up if we can't create the correct constraint
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     * Try to drop the corrected FK and restore the original (incorrect) FK name
     * in case of rollback. This attempts to recreate the prior (incorrect) state
     * so the migration is reversible.
     *
     * @return void
     */
    public function down()
    {
        try {
            DB::statement('ALTER TABLE `specializations_salls` DROP FOREIGN KEY `fk_specializations_salls_specialization_id`');
        } catch (\Exception $e) {
            // ignore
        }

        // Recreate the original (incorrect) foreign key back to doctors.id to restore previous state
        try {
            DB::statement('ALTER TABLE `specializations_salls` ADD CONSTRAINT `doctor_salls_doctor_id_foreign` FOREIGN KEY (`specialization_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // ignore errors here
            \Log::info('Could not recreate original doctor fk during rollback: ' . $e->getMessage());
        }
    }
};
