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
        Schema::create('admission_billing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->foreignId('procedure_id')->nullable()->constrained('admission_procedures')->onDelete('set null');
            $table->enum('item_type', ['procedure', 'service', 'nursing_care']);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('admission_id');
            $table->index('item_type');
            $table->index('is_paid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_billing_records');
    }
};
