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
        Schema::create('convention_rules', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('convention_id')->nullable()->constrained('conventions')->onDelete('cascade'); // Nullable foreign key
            $table->foreignId('avenant_id')->nullable()->constrained('avenants')->onDelete('cascade')->comment('If not null, this rule overrides the base convention rule.'); // Nullable foreign key
            $table->foreignId('prestation_id')->nullable()->constrained('prestations')->onDelete('cascade'); // Nullable foreign key
            $table->decimal('coverage_percentage', 5, 2)->nullable(); // Nullable decimal
            $table->decimal('negotiated_price', 15, 2)->nullable(); // Nullable decimal
            $table->decimal('financial_cap', 15, 2)->nullable()->comment('Plafond for this specific prestation'); // Nullable decimal
            $table->boolean('is_covered')->default(true); // Not null with default
            $table->timestamps(); // Adds created_at and updated_at columns (implied, good practice)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convention_rules');
    }
};
