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
       // Schema::table('coffre_transactions', function (Blueprint $table) {
            //coffre_destination
            //send for coffre to another 
         //   $table->foreignId('dest_coffre_id')->constrained('coffres')->onDelete('cascade');
             // Indexes
         //   $table->index(['coffre_id', 'created_at']);
         //   $table->index(['user_id', 'created_at']);
         //   $table->index('transaction_type');

     //   });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coffre_transactions', function (Blueprint $table) {
            //
        });
    }
};
