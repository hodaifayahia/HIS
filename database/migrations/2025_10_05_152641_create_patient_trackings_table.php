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
        Schema::create('patient_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_navette_item_id')->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('salle_id')->nullable()->constrained('salls')->onDelete('set null');
            $table->foreignId('specialization_id')->nullable()->constrained('specializations')->onDelete('set null');
            $table->foreignId('prestation_id')->nullable()->constrained('prestations')->onDelete('set null');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->string('status')->default('waiting'); // waiting, in_progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_trackings');
    }
};
