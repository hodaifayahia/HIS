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
        Schema::create('caisse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->foreignId('caisse_session_id')->constrained('caisse_sessions')->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('have_problems')->default(false);

            $table->decimal('amount_sended', 15, 2)->default(0);
            $table->decimal('amount_received', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('status')->default('pending'); // pending|accepted|rejected|cancelled
            $table->string('transfer_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_transfers');
    }
};
