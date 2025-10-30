<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reserves', function (Blueprint $table) {
            $table->id();

            // Core relations
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('pharmacy_product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('reserved_by')->constrained('users')->cascadeOnDelete();

            // Reservation data
            $table->string('reservation_code')->unique();
            $table->unsignedInteger('quantity');
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->nullable();

            // Workflow
            $table->string('status')->default('pending');

            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();

            // Misc
            $table->string('source')->nullable();
            $table->text('reservation_notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reserves');
    }
};
