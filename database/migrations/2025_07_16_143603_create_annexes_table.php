<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnexesTable extends Migration
{
    public function up()
    {
        Schema::create('annexes', function (Blueprint $table) {
            $table->id();
            $table->string('annex_name');
            $table->unsignedBigInteger('convention_id');
            $table->unsignedBigInteger('service_id');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('min_price');
            $table->string('prestation_prix_status')->default('empty');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Foreign key constraints
            $table->foreign('convention_id')->references('id')->on('conventions')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
            
            // Indexes
            $table->index('convention_id');
            $table->index('service_id');
            $table->index('annex_name');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('annexes');
    }
}
