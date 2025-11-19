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
        // Add indexes to commonly queried columns across all tables
        // Only add if column exists

        // Users table - highly queried for authentication and searches
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = Schema::getColumnListing('users');

                if (in_array('email', $columns) && ! $this->indexExists('users', 'users_email_index')) {
                    $table->index('email', 'users_email_index');
                }
                if (in_array('role', $columns) && ! $this->indexExists('users', 'users_role_index')) {
                    $table->index('role', 'users_role_index');
                }
                if (in_array('deleted_at', $columns) && ! $this->indexExists('users', 'users_deleted_at_index')) {
                    $table->index('deleted_at', 'users_deleted_at_index');
                }
                if (in_array('is_active', $columns) && ! $this->indexExists('users', 'users_is_active_index')) {
                    $table->index('is_active', 'users_is_active_index');
                }
                if (in_array('created_at', $columns) && ! $this->indexExists('users', 'users_created_at_index')) {
                    $table->index('created_at', 'users_created_at_index');
                }
            });
        }

        // Appointments table - frequently filtered by date, doctor, patient
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $columns = Schema::getColumnListing('appointments');

                if (in_array('patient_id', $columns) && ! $this->indexExists('appointments', 'appointments_patient_id_index')) {
                    $table->index('patient_id', 'appointments_patient_id_index');
                }
                if (in_array('doctor_id', $columns) && ! $this->indexExists('appointments', 'appointments_doctor_id_index')) {
                    $table->index('doctor_id', 'appointments_doctor_id_index');
                }
                if (in_array('status', $columns) && ! $this->indexExists('appointments', 'appointments_status_index')) {
                    $table->index('status', 'appointments_status_index');
                }
                if (in_array('appointment_date', $columns) && ! $this->indexExists('appointments', 'appointments_appointment_date_index')) {
                    $table->index('appointment_date', 'appointments_appointment_date_index');
                }
                if (in_array('deleted_at', $columns) && ! $this->indexExists('appointments', 'appointments_deleted_at_index')) {
                    $table->index('deleted_at', 'appointments_deleted_at_index');
                }
            });
        }

        // Patients table
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                $columns = Schema::getColumnListing('patients');

                if (in_array('deleted_at', $columns) && ! $this->indexExists('patients', 'patients_deleted_at_index')) {
                    $table->index('deleted_at', 'patients_deleted_at_index');
                }
                if (in_array('created_at', $columns) && ! $this->indexExists('patients', 'patients_created_at_index')) {
                    $table->index('created_at', 'patients_created_at_index');
                }
            });
        }

        // Consultations table
        if (Schema::hasTable('consultations')) {
            Schema::table('consultations', function (Blueprint $table) {
                $columns = Schema::getColumnListing('consultations');

                if (in_array('patient_id', $columns) && ! $this->indexExists('consultations', 'consultations_patient_id_index')) {
                    $table->index('patient_id', 'consultations_patient_id_index');
                }
                if (in_array('doctor_id', $columns) && ! $this->indexExists('consultations', 'consultations_doctor_id_index')) {
                    $table->index('doctor_id', 'consultations_doctor_id_index');
                }
                if (in_array('created_at', $columns) && ! $this->indexExists('consultations', 'consultations_created_at_index')) {
                    $table->index('created_at', 'consultations_created_at_index');
                }
            });
        }

        // Fiche Navette Items
        if (Schema::hasTable('fiche_navette_items')) {
            Schema::table('fiche_navette_items', function (Blueprint $table) {
                $columns = Schema::getColumnListing('fiche_navette_items');

                if (in_array('patient_id', $columns) && ! $this->indexExists('fiche_navette_items', 'fiche_navette_items_patient_id_index')) {
                    $table->index('patient_id', 'fiche_navette_items_patient_id_index');
                }
                if (in_array('status', $columns) && ! $this->indexExists('fiche_navette_items', 'fiche_navette_items_status_index')) {
                    $table->index('status', 'fiche_navette_items_status_index');
                }
                if (in_array('created_at', $columns) && ! $this->indexExists('fiche_navette_items', 'fiche_navette_items_created_at_index')) {
                    $table->index('created_at', 'fiche_navette_items_created_at_index');
                }
            });
        }

        // User Specializations pivot table
        if (Schema::hasTable('user_specializations')) {
            Schema::table('user_specializations', function (Blueprint $table) {
                $columns = Schema::getColumnListing('user_specializations');

                if (in_array('user_id', $columns) && ! $this->indexExists('user_specializations', 'user_specializations_user_id_index')) {
                    $table->index('user_id', 'user_specializations_user_id_index');
                }
                if (in_array('specialization_id', $columns) && ! $this->indexExists('user_specializations', 'user_specializations_specialization_id_index')) {
                    $table->index('specialization_id', 'user_specializations_specialization_id_index');
                }
                if (in_array('status', $columns) && ! $this->indexExists('user_specializations', 'user_specializations_status_index')) {
                    $table->index('status', 'user_specializations_status_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all indexes created in up()
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_email_index');
                $table->dropIndex('users_role_index');
                $table->dropIndex('users_deleted_at_index');
                $table->dropIndex('users_is_active_index');
                $table->dropIndex('users_created_at_index');
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex('appointments_patient_id_index');
                $table->dropIndex('appointments_doctor_id_index');
                $table->dropIndex('appointments_status_index');
                $table->dropIndex('appointments_appointment_date_index');
                $table->dropIndex('appointments_deleted_at_index');
                $table->dropIndex('appointments_doctor_date_status_index');
            });
        }

        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropIndex('patients_deleted_at_index');
                $table->dropIndex('patients_created_at_index');
            });
        }

        if (Schema::hasTable('consultations')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->dropIndex('consultations_patient_id_index');
                $table->dropIndex('consultations_doctor_id_index');
                $table->dropIndex('consultations_created_at_index');
                $table->dropIndex('consultations_deleted_at_index');
            });
        }

        if (Schema::hasTable('fiche_navette_items')) {
            Schema::table('fiche_navette_items', function (Blueprint $table) {
                $table->dropIndex('fiche_navette_items_patient_id_index');
                $table->dropIndex('fiche_navette_items_status_index');
                $table->dropIndex('fiche_navette_items_created_at_index');
            });
        }

        if (Schema::hasTable('user_specializations')) {
            Schema::table('user_specializations', function (Blueprint $table) {
                $table->dropIndex('user_specializations_user_id_index');
                $table->dropIndex('user_specializations_specialization_id_index');
                $table->dropIndex('user_specializations_status_index');
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?',
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
