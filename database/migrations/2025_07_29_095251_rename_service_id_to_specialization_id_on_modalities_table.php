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
        Schema::table('modalities', function (Blueprint $table) {
            // 1. Drop the existing foreign key constraint
            // Laravel's default foreign key naming convention is table_column_foreign
            // So, for 'service_id' on 'modalities', it would likely be 'modalities_service_id_foreign'.
            // You can also pass an array of columns, and Laravel will guess the name.
            $table->dropForeign(['service_id']);

            // 2. Rename the column
            $table->renameColumn('service_id', 'specialization_id');
        });

        // Add the new foreign key constraint in a separate Schema::table call
        // This is safer as some database drivers might have issues with
        // renaming a column and adding a foreign key in the same block.
        Schema::table('modalities', function (Blueprint $table) {
            // 3. Add the new foreign key constraint
            $table->foreignId('specialization_id')
                  ->nullable() // Ensure it's nullable if service_id was nullable
                  ->constrained('specializations') // References 'specializations' table
                  ->onDelete('set null'); // Match original onDelete behavior
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modalities', function (Blueprint $table) {
            // 1. Drop the new foreign key constraint
            $table->dropForeign(['specialization_id']);

            // 2. Rename the column back
            $table->renameColumn('specialization_id', 'service_id');
        });

        // Add the original foreign key constraint back
        Schema::table('modalities', function (Blueprint $table) {
            // 3. Add the old foreign key constraint
            $table->foreignId('service_id')
                  ->nullable()
                  ->constrained('services') // References 'services' table
                  ->onDelete('set null');
        });
    }
};