<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('prestation_package_reception', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('package_id')
        //         ->constrained('prestation_packages')
        //         ->cascadeOnDelete();

        //     $table->foreignId('prestation_id')
        //         ->constrained('prestations')
        //         ->cascadeOnDelete();

        //     $table->foreignId('doctor_id')
        //         ->nullable()
        //         ->constrained('doctors')
        //         ->nullOnDelete();

        //     $table->timestamps();
        // });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestation_package_reception');
    }
};
