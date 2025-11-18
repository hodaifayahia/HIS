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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();

            // Who: Patient information
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');

            // Which doctor
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');

            // Type: 'surgery' (requires upfront payment) or 'nursing' (pay after services)
            $table->enum('type', ['surgery', 'nursing'])->default('nursing');

            // Status: admitted | in_service | document_pending | ready_for_discharge
            $table->enum('status', ['admitted', 'in_service', 'document_pending', 'ready_for_discharge'])->default('admitted');

            $table->foreignId('fiche_navette_id')->nullable()->constrained('fiche_navettes')->onDelete('set null');
            // Admission timestamps
            $table->dateTime('admitted_at')->nullable();
            $table->dateTime('discharged_at')->nullable();

            // Initial Prestation (required for surgery, optional for nursing)
            $table->foreignId('initial_prestation_id')->nullable()->constrained('prestations')->onDelete('set null');

            // Documents verification flag
            $table->boolean('documents_verified')->default(false);

            // System fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Indexes for performance
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('type');
            $table->index('status');
            $table->index('admitted_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
