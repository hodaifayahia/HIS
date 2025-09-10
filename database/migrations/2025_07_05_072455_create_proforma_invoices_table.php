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
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->string('proforma_number', 191)->unique(); // Unique and not null
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('organisme_id')->constrained('organismes')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('adherent_id')->nullable()->constrained('patients')->onDelete('set null')->comment('The primary insured member'); // Nullable foreign key
            $table->date('pricing_date')->comment('The date used to calculate the rules'); // Not null
            $table->date('issue_date'); // Not null
            $table->date('expiry_date'); // Not null
            $table->string('status')->comment('draft, sent, accepted, expired'); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
