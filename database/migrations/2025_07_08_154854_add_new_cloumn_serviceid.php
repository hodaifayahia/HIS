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
        Schema::table('specializations', function (Blueprint $table) {
            //  $table->foreignId('service_id')->constrained('services')->onDelete('cascade'); // Not null foreign key
            //  $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('specializations', function (Blueprint $table) {
               $table->dropColumn('service_id');
        });
    }
};
