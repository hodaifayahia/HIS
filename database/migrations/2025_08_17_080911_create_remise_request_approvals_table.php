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
       Schema::create('remise_request_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('remise_request_id')->constrained('remise_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // who acted

            $table->enum('role', ['receiver', 'approver']); // distinguish between them
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remise_request_approvals');
    }
};
