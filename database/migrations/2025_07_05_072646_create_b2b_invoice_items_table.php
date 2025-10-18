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
         Schema::create('b2b_invoice_items', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('b2b_invoice_id')->constrained('b2b_invoices')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('fiche_navette_item_id')->unique()->constrained('fiche_navette_items')->onDelete('cascade')->comment('An item can only be invoiced once'); // Unique and not null foreign key
            $table->decimal('amount', 15, 2)->nullable(); // Nullable decimal
            $table->timestamps(); // Adds created_at and updated_at columns (implied, good practice)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_invoice_items');
    }
};
