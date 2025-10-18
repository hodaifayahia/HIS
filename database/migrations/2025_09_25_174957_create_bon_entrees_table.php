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
        Schema::create('bon_entrees', function (Blueprint $table) {
            $table->id();
            $table->string('bon_entree_code')->unique();
            $table->foreignId('bon_reception_id')->nullable()->constrained('bon_receptions')->onDelete('set null');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('set null');
            $table->enum('status', ['draft', 'validated', 'transferred', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('service_abv')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['fournisseur_id', 'created_at']);
            $table->index('bon_reception_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_entrees');
    }
};
