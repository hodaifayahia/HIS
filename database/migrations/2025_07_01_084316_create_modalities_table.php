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
       Schema::create('modalities', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->string('name')->comment('e.g., IRM Siemens 1.5T - Salle 1'); // Not null
            $table->string('internal_code', 191)->unique(); // Unique and not null
            $table->foreignId('modality_type_id')->nullable()->constrained('modality_types')->onDelete('set null'); // Nullable foreign key
            $table->string('dicom_ae_title')->nullable(); // Nullable string
            $table->integer('port')->nullable(); // Nullable integer
            $table->foreignId('physical_location_id')->nullable()->constrained('rooms')->onDelete('set null'); // Nullable foreign key to rooms
            $table->string('operational_status')->comment('operational, maintenance, out-of-service'); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modalities');
    }
};
