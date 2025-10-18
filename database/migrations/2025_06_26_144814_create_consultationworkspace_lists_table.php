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
      Schema::create('consultationworkspace_list', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('consultation_id')
                ->constrained('consultations')
                ->onDelete('cascade');

            $table->foreignId('consultation_workspace_id')
                ->constrained('consultationworkspaces')
                ->onDelete('cascade');

            $table->string('notes');
            $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultationworkspace_lists');
    }
};
