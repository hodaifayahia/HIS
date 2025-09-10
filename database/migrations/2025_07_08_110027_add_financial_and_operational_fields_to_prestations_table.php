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
        Schema::table('prestations', function (Blueprint $table) {
            // Add 'type' column
            // Assuming 'type' is a string and required, based on validation 'required|in:Médical,Chirurgical'
            $table->string('type')->after('specialization_id');

            // Add Financial Configuration fields
            // Correcting 'Tarif_de_nuit' to 'night_tariff' to match validation
            $table->decimal('night_tariff', 10, 2)->nullable()->after('vat_rate'); // Or float, but decimal is better for money
            $table->text('reimbursement_conditions')->nullable()->after('is_social_security_reimbursable');
            // non_applicable_discount_rules will likely be JSON
            $table->json('non_applicable_discount_rules')->nullable()->after('reimbursement_conditions');
            // fee_distribution_model is a string and required
            $table->string('fee_distribution_model')->default('percentage')->after('non_applicable_discount_rules'); // Added a default as it's required

            // Fee Distribution Shares
            // 'share' fields are nullable|string, often storing numbers or "10%"
            // Use `string` if you truly intend to store "10%" or similar.
            // If only numbers, `decimal` or `float` would be better. Sticking to `string` based on your validation.
            $table->string('primary_doctor_share', 50)->nullable()->after('fee_distribution_model');
            $table->boolean('primary_doctor_is_percentage')->default(false)->after('primary_doctor_share');
            $table->string('assistant_doctor_share', 50)->nullable()->after('primary_doctor_is_percentage');
            $table->boolean('assistant_doctor_is_percentage')->default(false)->after('assistant_doctor_share');
            $table->string('technician_share', 50)->nullable()->after('assistant_doctor_is_percentage');
            $table->boolean('technician_is_percentage')->default(false)->after('technician_share');
            $table->string('clinic_share', 50)->nullable()->after('technician_is_percentage');
            $table->boolean('clinic_is_percentage')->default(false)->after('clinic_share');

            // Operational & Clinical Configuration
            $table->json('required_prestations_info')->nullable()->after('default_duration_minutes');
            $table->text('patient_instructions')->nullable()->after('required_prestations_info');
            $table->json('required_consents')->nullable()->after('patient_instructions');

            // Clean up potentially unused old fields if you are sure they are no longer needed
            // If you are migrating from Tarif_de_nuit to night_tariff, you might remove the old one.
            // Schema::table('prestations', function (Blueprint $table) {
            //    $table->dropColumn('Tarif_de_nuit');
            //    $table->dropColumn('Tarif_de_nuit_is_active');
            // });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            // Drop the columns in reverse order of addition (or logical grouping)
            $table->dropColumn([
                'type',
                'night_tariff',
                'reimbursement_conditions',
                'non_applicable_discount_rules',
                'fee_distribution_model',
                'primary_doctor_share',
                'primary_doctor_is_percentage',
                'assistant_doctor_share',
                'assistant_doctor_is_percentage',
                'technician_share',
                'technician_is_percentage',
                'clinic_share',
                'clinic_is_percentage',
                'required_prestations_info',
                'patient_instructions',
                'required_consents',
            ]);

            // If you removed old columns in the up() method, uncomment these to reverse that change
            // $table->decimal('Tarif_de_nuit', 10, 2)->nullable();
            // $table->boolean('Tarif_de_nuit_is_active')->default(false);
        });
    }
};