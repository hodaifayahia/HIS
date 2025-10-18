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
        Schema::create('payment_type_overrides_log', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('fiche_navette_item_id')->nullable()->constrained('fiche_navette_items')->onDelete('set null'); // Nullable foreign key
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->string('original_payment_type')->nullable(); // Nullable string
            $table->string('new_payment_type')->nullable(); // Nullable string
            $table->text('justification'); // Not null
            $table->timestamps(); // Not null with default current timestamp
            // No updated_at as per diagram
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_type_overrides_log');
    }
};
