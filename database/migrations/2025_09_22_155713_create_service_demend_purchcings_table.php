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
        Schema::create('service_demand_purchasings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->string('demand_code');
            $table->date('expected_date')->nullable();
            $table->string('status')->default('draft'); // draft, sent, approved, rejected, completed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_demand_purchasings');
    }
};
