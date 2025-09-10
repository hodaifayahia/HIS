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
        // Create the 'prestation_pricing' table
        Schema::create('prestation_pricing', function (Blueprint $table) {
            // Primary key for the table
            $table->id();

            // Foreign key to the 'prestations' table.
            // This links a pricing entry to a specific prestation (service).
            $table->unsignedBigInteger('prestation_id');
            $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('cascade');

            // Foreign key to the 'annexes' table.
            // This links a pricing entry to a specific annex, as seen in the Vue.js code.
            $table->unsignedBigInteger('annex_id');
            $table->foreign('annex_id')->references('id')->on('annexes')->onDelete('cascade');

            // Global price of the prestation. This remains a decimal as it's the base price.
            $table->decimal('prix', 10, 2)->default(0.00);

            // Company's part as an absolute price.
            // Using decimal(10, 2) for precise currency storage.
            $table->decimal('company_price', 10, 2)->default(0.00);

            // Patient's part as an absolute price.
            // Using decimal(10, 2) for precise currency storage.
            $table->decimal('patient_price', 10, 2)->default(0.00);

            // New fields for tracking pricing calculation details
            $table->boolean('max_price_exceeded')->default(false);
            $table->decimal('original_company_share', 10, 2)->default(0.00);
            $table->decimal('original_patient_share', 10, 2)->default(0.00);

            $table->unsignedBigInteger('updated_by_id')->unsigned();
            $table->unsignedBigInteger('avenant_id')->unsigned();
            $table->foreign('avenant_id')->references('id')->on('avenants')->onDelete('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade');
            


            // Adds 'created_at' and 'updated_at' columns for timestamps.
            $table->timestamp('activation_at');
            $table->timestamps();

            // Add unique constraint to ensure a prestation has only one pricing
            // entry per annex. This prevents duplicate pricing for the same prestation
            // within the same annex.
            $table->unique(['prestation_id', 'annex_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the 'prestation_pricing' table if it exists when rolling back migrations.
        Schema::dropIfExists('prestation_pricing');
    }
};
