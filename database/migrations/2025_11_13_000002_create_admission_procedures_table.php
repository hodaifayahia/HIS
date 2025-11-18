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
        Schema::create('admission_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->foreignId('prestation_id')->nullable()->constrained('prestations')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->boolean('is_medication_conversion')->default(false);
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('admission_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_procedures');
    }
};
