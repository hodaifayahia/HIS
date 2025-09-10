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
       Schema::create('fiche_navette_items', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('fiche_navette_id')->constrained('fiche_navettes')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('prestation_id')->constrained('prestations')->onDelete('cascade'); // Not null foreign key
            $table->string('status')->comment('The core status engine for a single service, e.g., scheduled, awaiting-payment, visa-granted'); // Not null
            $table->decimal('base_price', 15, 2); // Not null
            $table->decimal('final_price', 15, 2)->comment('Price after all calculations'); // Not null
            $table->decimal('patient_share', 15, 2); // Not null
            $table->decimal('organisme_share', 15, 2); // Not null
            $table->foreignId('primary_clinician_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->foreignId('assistant_clinician_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->foreignId('modality_id')->nullable()->constrained('modalities')->onDelete('set null'); // Nullable foreign key
            $table->date('prise_en_charge_date')->nullable()->comment('The crucial date from the B2B guarantee document'); // Nullable date
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_navette_items');
    }
};
