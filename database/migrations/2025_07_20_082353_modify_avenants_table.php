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
        Schema::table('avenants', function (Blueprint $table) {
            // Rename existing column
            $table->renameColumn('activation_datetime', 'activation_at');
            
            // Add new columns
            $table->timestamp('inactive_at')->nullable()->comment('The exact moment the changes become inactive');
            $table->string('head')->comment('yes, no');
            $table->foreignId('updated_by_id')->constrained('users')->onDelete('cascade')->comment('User who last updated this record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avenants', function (Blueprint $table) {
            // Remove added columns
            $table->dropForeign(['updated_by_id']);
            $table->dropColumn('updated_by_id');
            $table->dropColumn('head');
            $table->dropColumn('inactive_at');
            
            // Rename column back
            $table->renameColumn('activation_at', 'activation_datetime');
        });
    }
};
