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
        Schema::table('patients', function (Blueprint $table) {
            // Contact Information
            $table->string('fax_number', 20)->nullable()->after('phone');

            // Identity Document Fields
            $table->enum('identity_document_type', [
                'national_card',
                'passport',
                'foreigner_card',
                'drivers_license',
                'other',
            ])->nullable()->after('Idnum')->comment('Type of identity document');

            $table->date('identity_issued_on')->nullable()->after('identity_document_type')->comment('Date when identity was issued');
            $table->string('identity_issued_by', 255)->nullable()->after('identity_issued_on')->comment('Authority that issued the identity');

            // Specific Document Numbers
            $table->string('passport_number', 50)->nullable()->unique()->after('identity_issued_by')->comment('Passport number');
            $table->string('professional_badge_number', 50)->nullable()->after('passport_number')->comment('Professional badge or license number');
            $table->string('foreigner_card_number', 50)->nullable()->unique()->after('professional_badge_number')->comment('Foreigner card number');

            // Birth Place Enhancement
            $table->boolean('is_birth_place_presumed')->default(false)->after('birth_place')->comment('Flag indicating if birth place is presumed');

            // Additional IDs Storage (JSON)
            $table->json('additional_ids')->nullable()->after('is_birth_place_presumed')->comment('JSON array for storing multiple document IDs');

            // Add indexes for performance
            $table->index('passport_number')->comment('Index for passport lookup');
            $table->index('foreigner_card_number')->comment('Index for foreigner card lookup');
            $table->index('professional_badge_number')->comment('Index for professional badge lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['passport_number']);
            $table->dropIndex(['foreigner_card_number']);
            $table->dropIndex(['professional_badge_number']);

            // Drop columns
            $table->dropColumn([
                'fax_number',
                'identity_document_type',
                'identity_issued_on',
                'identity_issued_by',
                'passport_number',
                'professional_badge_number',
                'foreigner_card_number',
                'is_birth_place_presumed',
                'additional_ids',
            ]);
        });
    }
};
