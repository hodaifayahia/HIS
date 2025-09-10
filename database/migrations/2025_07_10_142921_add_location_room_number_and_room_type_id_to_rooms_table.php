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
            // Add new columns
            $table->string('location')->nullable()->after('pavilion_id');
            $table->string('room_number')->nullable()->after('name');
            $table->string('number_of_people')->nullable()->after('name');

            // Drop the old 'room_type' string column
            $table->dropColumn('room_type');

            // Add the new 'room_type_id' column (without foreign key constraint initially)
            $table->unsignedBigInteger('room_type_id')->nullable()->after('room_number');
            
            // Add foreign key constraint after room_types table is created
            // This will be handled by a separate migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
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