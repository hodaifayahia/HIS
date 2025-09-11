<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds indexes for performance optimization
     * across all tables in the appointment system.
     */
    public function up(): void
    {
        // Users table indexes - Skip email (already indexed) and created_at (may already exist)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Only add if not exists
                if (!$this->indexExists('users', 'users_updated_at_index')) {
                    $table->index('updated_at');
                }
            });
        }

        // Specializations table indexes
        if (Schema::hasTable('specializations')) {
            Schema::table('specializations', function (Blueprint $table) {
                if (!$this->indexExists('specializations', 'specializations_created_by_index')) {
                    $table->index('created_by');
                }
                if (!$this->indexExists('specializations', 'specializations_created_at_index')) {
                    $table->index('created_at');
                }
                if (!$this->indexExists('specializations', 'specializations_updated_at_index')) {
                    $table->index('updated_at');
                }
                if (!$this->indexExists('specializations', 'specializations_deleted_at_index')) {
                    $table->index('deleted_at');
                }
            });
        }

        // Doctors table indexes
        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                if (!$this->indexExists('doctors', 'doctors_specialization_id_index')) {
                    $table->index('specialization_id');
                }
                if (!$this->indexExists('doctors', 'doctors_user_id_index')) {
                    $table->index('user_id');
                }
                if (!$this->indexExists('doctors', 'doctors_created_by_index')) {
                    $table->index('created_by');
                }
                if (!$this->indexExists('doctors', 'doctors_frequency_index')) {
                    $table->index('frequency');
                }
                if (!$this->indexExists('doctors', 'doctors_specialization_id_frequency_index')) {
                    $table->index(['specialization_id', 'frequency']);
                }
                if (!$this->indexExists('doctors', 'doctors_user_id_specialization_id_index')) {
                    $table->index(['user_id', 'specialization_id']);
                }
                if (!$this->indexExists('doctors', 'doctors_created_at_index')) {
                    $table->index('created_at');
                }
                if (!$this->indexExists('doctors', 'doctors_updated_at_index')) {
                    $table->index('updated_at');
                }
                if (!$this->indexExists('doctors', 'doctors_deleted_at_index')) {
                    $table->index('deleted_at');
                }
            });
        }

        // Patients table indexes
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (!$this->indexExists('patients', 'patients_phone_index')) {
                    $table->index('phone');
                }
                if (!$this->indexExists('patients', 'patients_dateofbirth_index')) {
                    $table->index('dateOfBirth');
                }
                if (!$this->indexExists('patients', 'patients_created_by_index')) {
                    $table->index('created_by');
                }
                if (!$this->indexExists('patients', 'patients_firstname_lastname_index')) {
                    $table->index(['Firstname', 'Lastname']);
                }
                if (!$this->indexExists('patients', 'patients_created_at_index')) {
                    $table->index('created_at');
                }
                if (!$this->indexExists('patients', 'patients_updated_at_index')) {
                    $table->index('updated_at');
                }
                if (!$this->indexExists('patients', 'patients_deleted_at_index')) {
                    $table->index('deleted_at');
                }
            });
        }

        // Appointments table indexes - Most important for performance
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!$this->indexExists('appointments', 'appointments_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (!$this->indexExists('appointments', 'appointments_patient_id_index')) {
                    $table->index('patient_id');
                }
                if (!$this->indexExists('appointments', 'appointments_appointment_date_index')) {
                    $table->index('appointment_date');
                }
                if (!$this->indexExists('appointments', 'appointments_status_index')) {
                    $table->index('status');
                }
                if (!$this->indexExists('appointments', 'appointments_created_by_index')) {
                    $table->index('created_by');
                }
                if (!$this->indexExists('appointments', 'appointments_doctor_id_appointment_date_index')) {
                    $table->index(['doctor_id', 'appointment_date']);
                }
                if (!$this->indexExists('appointments', 'appointments_patient_id_appointment_date_index')) {
                    $table->index(['patient_id', 'appointment_date']);
                }
                if (!$this->indexExists('appointments', 'appointments_appointment_date_status_index')) {
                    $table->index(['appointment_date', 'status']);
                }
                if (!$this->indexExists('appointments', 'appointments_created_at_index')) {
                    $table->index('created_at');
                }
                if (!$this->indexExists('appointments', 'appointments_updated_at_index')) {
                    $table->index('updated_at');
                }
                if (!$this->indexExists('appointments', 'appointments_deleted_at_index')) {
                    $table->index('deleted_at');
                }
            });
        }

        // Schedules table indexes
        if (Schema::hasTable('schedules')) {
            Schema::table('schedules', function (Blueprint $table) {
                if (!$this->indexExists('schedules', 'schedules_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (!$this->indexExists('schedules', 'schedules_day_of_week_index')) {
                    $table->index('day_of_week');
                }
                if (!$this->indexExists('schedules', 'schedules_is_active_index')) {
                    $table->index('is_active');
                }
                if (!$this->indexExists('schedules', 'schedules_doctor_id_day_of_week_index')) {
                    $table->index(['doctor_id', 'day_of_week']);
                }
                if (!$this->indexExists('schedules', 'schedules_doctor_id_is_active_index')) {
                    $table->index(['doctor_id', 'is_active']);
                }
                if (!$this->indexExists('schedules', 'schedules_created_at_index')) {
                    $table->index('created_at');
                }
                if (!$this->indexExists('schedules', 'schedules_updated_at_index')) {
                    $table->index('updated_at');
                }
                if (!$this->indexExists('schedules', 'schedules_deleted_at_index')) {
                    $table->index('deleted_at');
                }
            });
        }

        // Only add indexes for tables that exist and are commonly queried
        $this->addOptionalTableIndexes();
    }

    /**
     * Add indexes for optional/conditional tables
     */
    private function addOptionalTableIndexes(): void
    {
        // Waitlist table indexes
        if (Schema::hasTable('waitlist')) {
            Schema::table('waitlist', function (Blueprint $table) {
                if (!$this->indexExists('waitlist', 'waitlist_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (!$this->indexExists('waitlist', 'waitlist_patient_id_index')) {
                    $table->index('patient_id');
                }
                if (!$this->indexExists('waitlist', 'waitlist_specialization_id_index')) {
                    $table->index('specialization_id');
                }
                if (!$this->indexExists('waitlist', 'waitlist_importance_index')) {
                    $table->index('importance');
                }
                if (!$this->indexExists('waitlist', 'waitlist_doctor_id_importance_index')) {
                    $table->index(['doctor_id', 'importance']);
                }
            });
        }

        // Consultations table indexes
        if (Schema::hasTable('consultations')) {
            Schema::table('consultations', function (Blueprint $table) {
                if (!$this->indexExists('consultations', 'consultations_patient_id_index')) {
                    $table->index('patient_id');
                }
                if (!$this->indexExists('consultations', 'consultations_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (!$this->indexExists('consultations', 'consultations_appointment_id_index')) {
                    $table->index('appointment_id');
                }
                if (!$this->indexExists('consultations', 'consultations_doctor_id_patient_id_index')) {
                    $table->index(['doctor_id', 'patient_id']);
                }
            });
        }

        // Prescriptions table indexes
        if (Schema::hasTable('prescriptions')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                if (!$this->indexExists('prescriptions', 'prescriptions_consultation_id_index')) {
                    $table->index('consultation_id');
                }
                if (!$this->indexExists('prescriptions', 'prescriptions_patient_id_index')) {
                    $table->index('patient_id');
                }
                if (!$this->indexExists('prescriptions', 'prescriptions_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (!$this->indexExists('prescriptions', 'prescriptions_prescription_date_index')) {
                    $table->index('prescription_date');
                }
                if (!$this->indexExists('prescriptions', 'prescriptions_patient_id_prescription_date_index')) {
                    $table->index(['patient_id', 'prescription_date']);
                }
            });
        }

        // Excluded dates table indexes
        if (Schema::hasTable('excluded_dates')) {
            Schema::table('excluded_dates', function (Blueprint $table) {
                if (!$this->indexExists('excluded_dates', 'excluded_dates_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (!$this->indexExists('excluded_dates', 'excluded_dates_start_date_index')) {
                    $table->index('start_date');
                }
                if (!$this->indexExists('excluded_dates', 'excluded_dates_end_date_index')) {
                    $table->index('end_date');
                }
                if (!$this->indexExists('excluded_dates', 'excluded_dates_doctor_id_start_date_index')) {
                    $table->index(['doctor_id', 'start_date']);
                }
                if (!$this->indexExists('excluded_dates', 'excluded_dates_doctor_id_end_date_index')) {
                    $table->index(['doctor_id', 'end_date']);
                }
            });
        }
    }

    /**
     * Check if an index exists on a table using raw SQL query
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define tables and their indexes to drop
        $tablesToClean = [
            'users' => ['updated_at'],
            'specializations' => ['created_by', 'created_at', 'updated_at', 'deleted_at'],
            'doctors' => [
                'specialization_id', 'user_id', 'created_by', 'frequency',
                'created_at', 'updated_at', 'deleted_at'
            ],
            'patients' => [
                'phone', 'dateOfBirth', 'created_by', 'created_at', 'updated_at', 'deleted_at'
            ],
            'appointments' => [
                'doctor_id', 'patient_id', 'appointment_date', 'status', 'created_by',
                'created_at', 'updated_at', 'deleted_at'
            ],
            'schedules' => [
                'doctor_id', 'day_of_week', 'is_active', 'created_at', 'updated_at', 'deleted_at'
            ],
            'waitlist' => [
                'doctor_id', 'patient_id', 'specialization_id', 'importance'
            ],
            'consultations' => ['patient_id', 'doctor_id', 'appointment_id'],
            'prescriptions' => [
                'consultation_id', 'patient_id', 'doctor_id', 'prescription_date'
            ],
            'excluded_dates' => ['doctor_id', 'start_date', 'end_date']
        ];

        foreach ($tablesToClean as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        try {
                            $table->dropIndex([$index]);
                        } catch (\Exception $e) {
                            // Index might not exist, continue
                        }
                    }
                });
            }
        }
    }
};
