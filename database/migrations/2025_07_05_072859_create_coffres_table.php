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
       Schema::create('coffres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('responsible_user_id')->constrained('users'); // User responsible for this vault
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffres');
    }
};
