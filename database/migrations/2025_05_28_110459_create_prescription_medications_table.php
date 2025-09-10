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
         Schema::create('prescription_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->string('cd_active_substance');
            $table->string('brand_name')->nullable();
            $table->string('pharmaceutical_form');
            $table->string('dose_per_intake');
            $table->string('num_intakes_per_time');
            $table->string('frequency');
            $table->string('duration_or_boxes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_medications');
    }
};
