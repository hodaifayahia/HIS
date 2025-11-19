<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Add missing identity and tracking columns
            if (! Schema::hasColumn('patients', 'is_faithful')) {
                $table->boolean('is_faithful')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('patients', 'fax_number')) {
                $table->string('fax_number')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('patients', 'identity_document_type')) {
                $table->string('identity_document_type')->nullable()->after('fax_number');
            }
            if (! Schema::hasColumn('patients', 'identity_issued_on')) {
                $table->date('identity_issued_on')->nullable()->after('identity_document_type');
            }
            if (! Schema::hasColumn('patients', 'identity_issued_by')) {
                $table->string('identity_issued_by')->nullable()->after('identity_issued_on');
            }
            if (! Schema::hasColumn('patients', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('identity_issued_by');
            }
            if (! Schema::hasColumn('patients', 'professional_badge_number')) {
                $table->string('professional_badge_number')->nullable()->after('passport_number');
            }
            if (! Schema::hasColumn('patients', 'foreigner_card_number')) {
                $table->string('foreigner_card_number')->nullable()->after('professional_badge_number');
            }
            if (! Schema::hasColumn('patients', 'is_birth_place_presumed')) {
                $table->boolean('is_birth_place_presumed')->default(false)->after('foreigner_card_number');
            }
            if (! Schema::hasColumn('patients', 'additional_ids')) {
                $table->json('additional_ids')->nullable()->after('is_birth_place_presumed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'is_faithful',
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
