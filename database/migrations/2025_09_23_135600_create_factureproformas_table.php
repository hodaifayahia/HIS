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
        Schema::create('factureproformas', function (Blueprint $table) {
            $table->id();
            $table->string('factureProformaCode')->unique();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs');
            $table->foreignId('service_demand_purchasing_id')->constrained('service_demand_purchasings')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('draft'); // draft, sent, paid, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factureproformas');
    }
};
