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
        // =================================================================
        //  CORE CRM TABLES
        // =================================================================

        // Stores all contacts associated with B2B partners (Organismes)
        // Schema::create('crm_contact_partenaires', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('organisme_id');
        //     $table->string('first_name');
        //     $table->string('last_name');
        //     $table->string('position')->nullable();
        //     $table->string('email')->unique()->nullable();
        //     $table->string('phone', 50)->nullable();
        //     $table->boolean('is_primary_contact')->default(false);
        //     $table->timestamps();
            
        //     $table->foreign('organisme_id')->references('id')->on('organismes')->onDelete('cascade');
        // });

        // // Central log for all interactions (polymorphic to link to patients or B2B contacts)
        // Schema::create('crm_interactions', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('subject');
        //     $table->enum('interaction_type', ['CALL', 'EMAIL', 'SMS', 'MEETING', 'IN_APP_MESSAGE']);
        //     $table->text('content')->nullable();
        //     $table->timestamp('interaction_date');
        //     $table->unsignedBigInteger('created_by_user_id');
            
        //     // Polymorphic relationship to link to either a Patient or a B2B Contact
        //     $table->unsignedBigInteger('interactable_id');
        //     $table->string('interactable_type');
            
        //     $table->timestamps();
            
        //     $table->foreign('created_by_user_id')->references('id')->on('users');
        //     $table->index(['interactable_id', 'interactable_type']);
        // });

        // // =================================================================
        // //  B2C (PATIENT) CRM TABLES
        // // =================================================================

        // // For managing health/marketing campaigns
        // Schema::create('crm_campagnes', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->text('description')->nullable();
        //     $table->date('start_date')->nullable();
        //     $table->date('end_date')->nullable();
        //     $table->enum('status', ['DRAFT', 'ACTIVE', 'COMPLETED', 'ARCHIVED'])->default('DRAFT');
        //     $table->unsignedBigInteger('created_by_user_id')->nullable();
        //     $table->timestamps();
            
        //     $table->foreign('created_by_user_id')->references('id')->on('users');
        // });

        // // Pivot table linking campaigns to the patients who received them
        // Schema::create('crm_campagne_patient', function (Blueprint $table) {
        //     $table->unsignedBigInteger('campagne_id');
        //     $table->unsignedBigInteger('patient_id');
        //     $table->timestamp('sent_at')->nullable();
        //     $table->enum('status', ['SENT', 'DELIVERED', 'OPENED', 'CLICKED', 'FAILED'])->default('SENT');
            
        //     $table->primary(['campagne_id', 'patient_id']);
        //     $table->foreign('campagne_id')->references('id')->on('crm_campagnes')->onDelete('cascade');
        //     $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        // });

        // // Ticketing system for feedback, complaints, and suggestions
        // Schema::create('crm_tickets_feedback', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('patient_id');
        //     $table->string('subject');
        //     $table->text('description')->nullable();
        //     $table->enum('ticket_type', ['COMPLAINT', 'SUGGESTION', 'INQUIRY']);
        //     $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])->default('OPEN');
        //     $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
        //     $table->unsignedBigInteger('assigned_to_user_id')->nullable();
        //     $table->timestamp('resolved_at')->nullable();
        //     $table->timestamps();
            
        //     $table->foreign('patient_id')->references('id')->on('patients');
        //     $table->foreign('assigned_to_user_id')->references('id')->on('users');
        // });

        // // =================================================================
        // //  B2B (PARTNER) CRM TABLES
        // // =================================================================

        // // To track the sales pipeline for new corporate conventions
        // Schema::create('crm_opportunites_b2b', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('opportunite_name');
        //     $table->unsignedBigInteger('organisme_id');
        //     $table->enum('stage', ['PROSPECTING', 'QUALIFICATION', 'NEGOTIATION', 'WON', 'LOST'])->default('PROSPECTING');
        //     $table->decimal('estimated_value', 15, 2)->nullable();
        //     $table->date('close_date')->nullable();
        //     $table->unsignedBigInteger('assigned_to_user_id');
        //     $table->timestamps();
            
        //     $table->foreign('organisme_id')->references('id')->on('organismes');
        //     $table->foreign('assigned_to_user_id')->references('id')->on('users');
        // });

        // // Directory of external referring doctors
        // Schema::create('crm_medecins_referents', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('first_name');
        //     $table->string('last_name');
        //     $table->string('specialty')->nullable();
        //     $table->string('clinic_name')->nullable();
        //     $table->string('email')->unique()->nullable();
        //     $table->string('phone', 50)->nullable();
        //     $table->timestamps();
        // });

        // // Log to track which patient was referred by which doctor
        // Schema::create('crm_referrals', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('patient_id');
        //     $table->unsignedBigInteger('medecin_referent_id');
        //     $table->date('referral_date');
        //     $table->text('notes')->nullable();
        //     $table->unsignedBigInteger('created_by_user_id');
        //     $table->timestamps();
            
        //     // Explicitly define a shorter unique index name
        //     $table->unique(['patient_id', 'medecin_referent_id', 'referral_date'], 'crm_referrals_patient_med_ref_date_unique'); 
        //     $table->foreign('patient_id')->references('id')->on('patients');
        //     $table->foreign('medecin_referent_id')->references('id')->on('crm_medecins_referents');
        //     $table->foreign('created_by_user_id')->references('id')->on('users');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_referrals');
        Schema::dropIfExists('crm_medecins_referents');
        Schema::dropIfExists('crm_opportunites_b2b');
        Schema::dropIfExists('crm_tickets_feedback');
        Schema::dropIfExists('crm_campagne_patient');
        Schema::dropIfExists('crm_campagnes');
        Schema::dropIfExists('crm_interactions');
        Schema::dropIfExists('crm_contact_partenaires');
    }
};