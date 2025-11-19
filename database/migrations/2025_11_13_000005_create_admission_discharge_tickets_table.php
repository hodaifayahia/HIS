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
        if (!Schema::hasTable('admission_discharge_tickets')) {
            Schema::create('admission_discharge_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
                $table->string('ticket_number')->unique();
                $table->foreignId('authorized_by')->nullable()->constrained('doctors')->onDelete('set null');
                $table->dateTime('generated_at');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();

                // Indexes
                $table->index('admission_id');
                $table->index('ticket_number');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_discharge_tickets');
    }
};
