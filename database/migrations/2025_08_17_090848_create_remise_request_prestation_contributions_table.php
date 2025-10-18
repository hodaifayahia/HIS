<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remise_request_prestation_contributions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('remise_request_prestation_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['doctor', 'user']);
            $table->decimal('proposed_amount', 10, 2)->nullable();
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            // Foreign keys with short names
            $table->foreign('remise_request_prestation_id', 'rrp_contrib_prest_fk')
                  ->references('id')
                  ->on('remise_request_prestations')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'rrp_contrib_user_fk')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('approved_by', 'rrp_contrib_approved_fk')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remise_request_prestation_contributions');
    }
};
