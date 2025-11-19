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
        Schema::create('external_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_code')->unique();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->integer('total_items')->default(0);
            $table->integer('dispensed_items')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_prescriptions');
    }
};
