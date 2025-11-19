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
        Schema::create('service_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('code')->unique()->nullable();
            $table->string('color')->default('#3B82F6'); // Default blue color
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('created_at');
        });

        Schema::create('service_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_group_id');
            $table->unsignedBigInteger('service_id');
            $table->integer('sort_order')->default(0);

            // Audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('service_group_id')->references('id')->on('service_groups')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraint - a service can only belong to one group
            $table->unique(['service_group_id', 'service_id'], 'service_group_service_unique');

            // Indexes
            $table->index('service_group_id');
            $table->index('service_id');
            $table->index('sort_order');
        });

        // Add service_group_id to selling_settings table for group-based settings (if table exists)
        if (Schema::hasTable('selling_settings')) {
            Schema::table('selling_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('service_group_id')->nullable()->after('service_id');
                $table->foreign('service_group_id')->references('id')->on('service_groups')->onDelete('cascade');
                $table->index('service_group_id');

                // Add a check to ensure either service_id or service_group_id is set, but not both
                // Note: This will be enforced in application logic as MySQL doesn't support CHECK constraints well
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('selling_settings')) {
            Schema::table('selling_settings', function (Blueprint $table) {
                $table->dropForeign(['service_group_id']);
                $table->dropIndex(['service_group_id']);
                $table->dropColumn('service_group_id');
            });
        }

        Schema::dropIfExists('service_group_members');
        Schema::dropIfExists('service_groups');
    }
};
