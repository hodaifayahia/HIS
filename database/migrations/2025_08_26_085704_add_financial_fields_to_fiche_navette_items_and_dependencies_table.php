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
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            $table->decimal('remaining_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
        });

        Schema::table('item_dependencies', function (Blueprint $table) {
            $table->decimal('remaining_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            $table->dropColumn(['remaining_amount', 'paid_amount']);
            $table->dropColumn(['payment_status', 'payment_method']);
        });

        Schema::table('item_dependencies', function (Blueprint $table) {
            $table->dropColumn(['remaining_amount', 'paid_amount']);
            $table->dropColumn(['payment_status', 'payment_method']);
        });
    }
};