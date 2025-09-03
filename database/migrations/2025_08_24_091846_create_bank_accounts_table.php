<?php
// database/migrations/create_bank_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->onDelete('restrict');
            $table->string('account_name');
            $table->string('account_number')->unique();
            $table->string('currency', 3)->default('DZD');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('available_balance', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['bank_id', 'is_active']);
            $table->index('currency');
            $table->index('account_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
