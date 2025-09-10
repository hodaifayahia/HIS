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
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            //  $table->string('custom_name')->nullable()->after('prestation_id')->comment('Custom name for the prestation if needed');
            // convention id
//   $table->foreignId('convention_id')
//               ->nullable() // or not, depending on your needs
//               ->constrained('conventions') // This links it to the 'conventions' table
//               ->onDelete('set null'); // This is the action you want on delete
if (!Schema::hasColumn('fiche_navette_items', 'convention_id')) {
    $table->unsignedBigInteger('convention_id')->nullable()->after('modality_id');
                $table->foreign('convention_id')->references('id')->on('conventions')->onDelete('set null');
            }
            
            // Add uploaded_file field (JSON to store multiple files)
            if (!Schema::hasColumn('fiche_navette_items', 'uploaded_file')) {
                $table->json('uploaded_file')->nullable()->after('convention_id');
            }
            if (!Schema::hasColumn('fiche_navette_items', 'insured_id')) {
                $table->foreignId('insured_id')
                    ->nullable()
                    ->constrained('patients')
                    ->onDelete('set null')
                    ->after('patient_id');
            }
            if (!Schema::hasColumn('fiche_navette_items', 'patient_id')) {
                $table->unsignedBigInteger('patient_id')->nullable()->after('convention_id');
                $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            }

            // Add family_authorization field (JSON to store multiple authorizations)
            if (!Schema::hasColumn('fiche_navette_items', 'family_authorization')) {
                $table->json('family_authorization')->nullable()->after('uploaded_file');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            $table->dropColumn('custom_name');
            $table->dropForeign(['convention_id']);
            $table->dropColumn('convention_id');
        });
    }
};
