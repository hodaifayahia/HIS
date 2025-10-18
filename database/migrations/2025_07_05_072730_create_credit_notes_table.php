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
         Schema::create('credit_notes', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('b2b_invoice_id')->constrained('b2b_invoices')->onDelete('cascade')->comment('The invoice being corrected'); // Not null foreign key
            $table->text('reason')->nullable(); // Nullable text
            $table->decimal('amount', 15, 2); // Not null
            $table->date('issue_date')->nullable(); // Nullable date
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
