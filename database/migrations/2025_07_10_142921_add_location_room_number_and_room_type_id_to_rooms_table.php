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
    Schema::table('rooms', function (Blueprint $table) {
        $table->string('location')->nullable()->after('pavilion_id');
        $table->string('room_number')->nullable()->after('name');
        $table->string('number_of_people')->nullable()->after('name');

        // Drop old string column if exists
        if (Schema::hasColumn('rooms', 'room_type')) {
            $table->dropColumn('room_type');

            // Add the new 'room_type_id' foreign key
            // IMPORTANT: The 'room_types' table MUST exist before running this migration.
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->onDelete('set null')->after('room_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Drop the unique constraint first if it exists
            $table->dropUnique('unique_room_number_per_pavilion');

            // Drop the new 'room_type_id' foreign key
            $table->dropForeign(['room_type_id']);
            $table->dropColumn('room_type_id');

            // Re-add the old 'room_type' string column (if you need to revert completely)
            $table->string('room_type')->nullable()->after('room_number'); // Adjust nullable/default as per your original

            // Drop the new columns
            $table->dropColumn('room_number');
            $table->dropColumn('location');
            $table->dropColumn('number_of_people');
            
            // Drop the room_type_id column
            $table->dropColumn('room_type_id');

            // Re-add the old 'room_type' string column
            $table->string('room_type')->nullable()->after('room_number');
        });
    }
};