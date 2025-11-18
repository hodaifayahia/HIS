<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add critical performance indexes (simplified).
     */
    public function up(): void
    {
        // Only add indexes for tables and columns we're certain exist

        // Appointments - Critical for scheduling
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (! $this->hasIndex('appointments', 'appointments_patient_id_index')) {
                    $table->index('patient_id');
                }
                if (! $this->hasIndex('appointments', 'appointments_doctor_id_index')) {
                    $table->index('doctor_id');
                }
                if (! $this->hasIndex('appointments', 'appointments_appointment_date_index')) {
                    $table->index('appointment_date');
                }
                if (! $this->hasIndex('appointments', 'appointments_status_index')) {
                    $table->index('status');
                }
            });
        }

        // Financial Transactions - Critical for payment flows
        if (Schema::hasTable('financial_transactions')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                if (! $this->hasIndex('financial_transactions', 'financial_transactions_patient_id_index')) {
                    $table->index('patient_id');
                }
                if (! $this->hasIndex('financial_transactions', 'financial_transactions_caisse_session_id_index')) {
                    $table->index('caisse_session_id');
                }
                if (! $this->hasIndex('financial_transactions', 'financial_transactions_status_index')) {
                    $table->index('status');
                }
                if (! $this->hasIndex('financial_transactions', 'financial_transactions_transaction_type_index')) {
                    $table->index('transaction_type');
                }
            });
        }

        // Patients - Critical for search
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (! $this->hasIndex('patients', 'patients_phone_index')) {
                    $table->index('phone');
                }
                if (! $this->hasIndex('patients', 'patients_created_by_index')) {
                    $table->index('created_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes if needed (wrapped in try-catch to handle non-existent indexes)
        try {
            if (Schema::hasTable('appointments')) {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->dropIndex('appointments_patient_id_index');
                    $table->dropIndex('appointments_doctor_id_index');
                    $table->dropIndex('appointments_appointment_date_index');
                    $table->dropIndex('appointments_status_index');
                });
            }
        } catch (\Exception $e) {
            // Index might not exist
        }

        try {
            if (Schema::hasTable('financial_transactions')) {
                Schema::table('financial_transactions', function (Blueprint $table) {
                    $table->dropIndex('financial_transactions_patient_id_index');
                    $table->dropIndex('financial_transactions_caisse_session_id_index');
                    $table->dropIndex('financial_transactions_status_index');
                    $table->dropIndex('financial_transactions_transaction_type_index');
                });
            }
        } catch (\Exception $e) {
            // Index might not exist
        }

        try {
            if (Schema::hasTable('patients')) {
                Schema::table('patients', function (Blueprint $table) {
                    $table->dropIndex('patients_phone_index');
                    $table->dropIndex('patients_created_by_index');
                });
            }
        } catch (\Exception $e) {
            // Index might not exist
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);

        return count($indexes) > 0;
    }
};
