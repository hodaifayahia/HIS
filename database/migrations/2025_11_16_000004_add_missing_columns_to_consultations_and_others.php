<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Consultations - add status
        if (Schema::hasTable('consultations')) {
            Schema::table('consultations', function (Blueprint $table) {
                if (! Schema::hasColumn('consultations', 'status')) {
                    $table->string('status')->default('draft');
                }
            });
        }

        // Fiche Navettes - add notes and departure_time
        if (Schema::hasTable('fiche_navettes')) {
            Schema::table('fiche_navettes', function (Blueprint $table) {
                if (! Schema::hasColumn('fiche_navettes', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (! Schema::hasColumn('fiche_navettes', 'departure_time')) {
                    $table->timestamp('departure_time')->nullable();
                }
            });
        }

        // Doctors - add avatar and notes
        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                if (! Schema::hasColumn('doctors', 'avatar')) {
                    $table->string('avatar')->nullable();
                }
                if (! Schema::hasColumn('doctors', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        // Appointments - add appointment_booking_window
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (! Schema::hasColumn('appointments', 'appointment_booking_window')) {
                    $table->string('appointment_booking_window')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('fiche_navettes', function (Blueprint $table) {
            $table->dropColumn(['notes', 'departure_time']);
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'notes']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('appointment_booking_window');
        });
    }
};
