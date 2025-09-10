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
        Schema::create('b2b_invoices', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->string('invoice_number', 191)->unique(); // Unique and not null
            $table->foreignId('organisme_id')->constrained('organismes')->onDelete('cascade'); // Not null foreign key
            $table->date('issue_date')->nullable(); // Nullable date
            $table->date('due_date')->nullable(); // Nullable date
            $table->decimal('total_amount', 15, 2)->nullable(); // Nullable decimal
            $table->string('status')->comment('draft, sent, paid, partially-paid, overdue'); // Not null
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_invoices');
    }
};
