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
        Schema::create('prestation_packageitems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestation_package_id');
            $table->unsignedBigInteger('prestation_id');

            // Foreign keys
            $table->foreign('prestation_package_id')->references('id')->on('prestation_packages')->onDelete('cascade');
            $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('cascade');

            // Additional fields can be added here as needed
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestation_packageitems');
    }
};
