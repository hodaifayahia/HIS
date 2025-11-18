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
        Schema::table('users', function (Blueprint $table) {
            // Add index for soft deletes query
            $table->index('deleted_at', 'users_deleted_at_index');

            // Add index for role filtering
            $table->index('role', 'users_role_index');

            // Add index for ordering by created_at
            $table->index('created_at', 'users_created_at_index');

            // Add composite index for common query pattern (deleted_at + role)
            $table->index(['deleted_at', 'role'], 'users_deleted_at_role_index');

            // Add index for is_active filtering
            $table->index('is_active', 'users_is_active_index');
        });

        Schema::table('user_specializations', function (Blueprint $table) {
            // Add index for status filtering
            $table->index('status', 'user_spec_status_index');

            // Add composite index for user_id + status (critical for activeSpecializations relation)
            $table->index(['user_id', 'status'], 'user_spec_user_id_status_index');

            // Add composite index for specialization_id + status
            $table->index(['specialization_id', 'status'], 'user_spec_spec_id_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_deleted_at_index');
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_created_at_index');
            $table->dropIndex('users_deleted_at_role_index');
            $table->dropIndex('users_is_active_index');
        });

        Schema::table('user_specializations', function (Blueprint $table) {
            $table->dropIndex('user_spec_status_index');
            $table->dropIndex('user_spec_user_id_status_index');
            $table->dropIndex('user_spec_spec_id_status_index');
        });
    }
};
