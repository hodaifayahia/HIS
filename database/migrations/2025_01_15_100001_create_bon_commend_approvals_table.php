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
        Schema::create('bon_commend_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_commend_id')->constrained('bon_commends')->onDelete('cascade');
            $table->foreignId('approval_person_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2); // The amount being approved
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable(); // Notes from requester
            $table->text('approval_notes')->nullable(); // Notes from approver
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['status', 'created_at']);
            $table->index(['bon_commend_id', 'status']);
            $table->index('approval_person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_commend_approvals');
    }
};
