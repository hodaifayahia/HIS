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
        Schema::create('stockages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location');
            $table->integer('capacity')->nullable();
            $table->enum('type', ['warehouse', 'pharmacy', 'laboratory', 'emergency', 'storage_room', 'cold_room'])->default('warehouse');
            $table->enum('status', ['active', 'inactive', 'maintenance', 'under_construction'])->default('active');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->boolean('temperature_controlled')->default(false);
            $table->enum('security_level', ['low', 'medium', 'high', 'restricted'])->default('medium');
            $table->timestamps();

            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stockages');
    }
};
