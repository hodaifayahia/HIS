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
     Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('proforma_invoice_id')->constrained('proforma_invoices')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('prestation_id')->constrained('prestations')->onDelete('cascade'); // Not null foreign key
            $table->decimal('price', 15, 2)->nullable(); // Nullable decimal
            $table->decimal('patient_share', 15, 2)->nullable(); // Nullable decimal
            $table->decimal('organisme_share', 15, 2)->nullable(); // Nullable decimal
            $table->timestamps(); // Adds created_at and updated_at columns (implied, good practice)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};
