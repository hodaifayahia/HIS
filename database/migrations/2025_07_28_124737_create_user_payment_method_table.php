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
        Schema::create('user_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->table('users')->constrained()->onDelete('cascade');

            // CHANGE: Store the enum string value directly, not a foreign ID
            $table->json('payment_method_key'); // e.g., 'prepayment', 'postpayment', 'versement'

            $table->string('status')->default('active'); // e.g., 'active', 'inactive'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_method');
    }
};