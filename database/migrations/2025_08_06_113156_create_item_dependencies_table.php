<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('item_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_item_id')->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('dependent_item_id')->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->foreignId('convention_id')->nullable()->constrained('conventions')->onDelete('set null');

            // i want to add base price and final price and organisme share and convention id and remise_id
            // type of remise : convention or remise or both
            $table->foreignId('remise_id')->nullable()->constrained('remises')->onDelete('set null');
              $table->decimal('final_price_after_convention', 10, 2)->nullable();
            $table->decimal('final_price_after_remise', 10, 2)->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->decimal('organisme_share', 10, 2)->nullable();
            $table->enum('dependency_type', ['contraindication', 'prerequisite', 'alternative', 'required', 'optional']);
            $table->text('notes')->nullable();
            $table->boolean('is_package')->default(false)->comment('Indicates if this dependency is part of a custom package');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['parent_item_id', 'dependency_type']);
            $table->index('dependent_item_id');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_dependencies');
    }
};
