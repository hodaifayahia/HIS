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
        Schema::create('consignment_receptions', function (Blueprint $table) {
            $table->id();
            $table->string('consignment_code')->unique();
            $table->unsignedBigInteger('fournisseur_id');
            $table->date('reception_date');
            $table->string('unit_of_measure')->default('piece');
            $table->text('origin_note')->nullable();
            $table->string('reception_type')->default('consignment');
            $table->string('operation_type')->default('manual');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('fournisseur_id')
                ->references('id')
                ->on('fournisseurs')
                ->onDelete('restrict'); // Prevent deletion if consignment exists

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('confirmed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for frequent queries
            $table->index('fournisseur_id');
            $table->index('reception_date');
            $table->index('reception_type');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_receptions');
    }
};
