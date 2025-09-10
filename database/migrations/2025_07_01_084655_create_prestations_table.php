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
         Schema::create('prestations', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->string('name'); // Not null
            $table->string('internal_code', 191)->unique(); // Unique and not null
            $table->string('billing_code')->nullable()->comment('Official code for invoices'); // Nullable string
            $table->text('description')->nullable(); // Nullable text
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('specialization_id')->constrained('specializations')->onDelete('cascade'); // Not null foreign key
            $table->decimal('public_price', 15, 2)->comment('Standard price (HT)'); // Not null
            $table->decimal('vat_rate', 5, 2)->default(0.00); // Not null with default
            $table->decimal('consumables_cost', 15, 2)->nullable()->comment('For profitability calculation'); // Nullable decimal
            $table->string('default_payment_type')->comment('pre-pay, post-pay, versement'); // Not null
            $table->decimal('min_versement_amount', 15, 2)->nullable(); // Nullable decimal
            $table->integer('default_duration_minutes')->nullable()->comment('Estimated time to perform the service'); // Nullable integer
            $table->boolean('requires_hospitalization')->default(false); // Not null with default
            $table->integer('default_hosp_nights')->nullable(); // Nullable integer
            $table->foreignId('required_modality_type_id')->nullable()->constrained('modality_types')->onDelete('set null')->comment('e.g., Requires any MRI 1.5T'); // Nullable foreign key
            $table->boolean('is_active')->default(true); // Not null with default
            $table->boolean('is_social_security_reimbursable')->default(false); // Not null with default
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
