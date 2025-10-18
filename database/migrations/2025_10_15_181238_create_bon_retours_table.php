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
        Schema::create('bon_retours', function (Blueprint $table) {
            $table->id();
            $table->string('bon_retour_code')->unique();
            $table->unsignedBigInteger('bon_entree_id')->nullable()->comment('Reference to original bon entree');
            $table->unsignedBigInteger('fournisseur_id');
            $table->enum('return_type', ['defective', 'expired', 'wrong_delivery', 'overstock', 'quality_issue', 'other'])->default('defective');
            $table->enum('status', ['draft', 'pending', 'approved', 'completed', 'cancelled'])->default('draft');
            $table->string('service_abv')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->date('return_date')->default(now());
            $table->string('reference_invoice')->nullable();
            $table->boolean('credit_note_received')->default(false);
            $table->string('credit_note_number')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('bon_retour_code');
            $table->index('status');
            $table->index('return_type');
            $table->index('fournisseur_id');
            $table->index('service_abv');
            $table->index(['status', 'return_date']);

            // Foreign keys
            $table->foreign('bon_entree_id')
                ->references('id')
                ->on('bon_entrees')
                ->onDelete('set null');

            $table->foreign('fournisseur_id')
                ->references('id')
                ->on('fournisseurs')
                ->onDelete('restrict');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_retours');
    }
};
