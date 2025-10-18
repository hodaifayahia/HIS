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
        Schema::create('bon_receptions', function (Blueprint $table) {
            $table->id();
            $table->string('bonReceptionCode')->unique();

            // Reference to bon commend
            $table->foreignId('bon_commend_id')->nullable()->constrained('bon_commends')->onDelete('cascade');
            $table->date('bon_commend_date')->nullable();

            // Delivery note information
            $table->string('bon_livraison_numero')->nullable();
            $table->date('bon_livraison_date')->nullable();

            // Invoice information
            $table->string('facture_numero')->nullable();
            $table->date('facture_date')->nullable();

            // Supplier and user relationships
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('cascade');
            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Reception details
            $table->date('date_reception');
            $table->integer('nombre_colis')->default(0);
            $table->text('observation')->nullable();

            // Status
            $table->enum('status', ['pending', 'canceled', 'completed', 'rejected'])->default('pending');

            // Confirmation tracking
            $table->boolean('is_confirmed')->default(false);
            $table->datetime('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');

            // Attachments (JSON array of file paths)
            $table->json('attachments')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'date_reception']);
            $table->index(['fournisseur_id', 'status']);
            $table->index('bon_commend_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_receptions');
    }
};
