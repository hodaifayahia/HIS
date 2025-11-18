<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            // Add missing critical columns
            if (! Schema::hasColumn('bon_receptions', 'bonReceptionCode')) {
                $table->string('bonReceptionCode')->unique()->after('id');
            }
            if (! Schema::hasColumn('bon_receptions', 'bon_commend_id')) {
                $table->unsignedBigInteger('bon_commend_id')->nullable()->after('bonReceptionCode');
            }
            if (! Schema::hasColumn('bon_receptions', 'bon_commend_date')) {
                $table->date('bon_commend_date')->nullable()->after('bon_commend_id');
            }
            if (! Schema::hasColumn('bon_receptions', 'bon_livraison_numero')) {
                $table->string('bon_livraison_numero')->nullable()->after('bon_commend_date');
            }
            if (! Schema::hasColumn('bon_receptions', 'bon_livraison_date')) {
                $table->date('bon_livraison_date')->nullable()->after('bon_livraison_numero');
            }
            if (! Schema::hasColumn('bon_receptions', 'facture_numero')) {
                $table->string('facture_numero')->nullable()->after('bon_livraison_date');
            }
            if (! Schema::hasColumn('bon_receptions', 'facture_date')) {
                $table->date('facture_date')->nullable()->after('facture_numero');
            }
            if (! Schema::hasColumn('bon_receptions', 'fournisseur_id')) {
                $table->unsignedBigInteger('fournisseur_id')->nullable()->after('facture_date');
            }
            if (! Schema::hasColumn('bon_receptions', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('fournisseur_id');
            }
            if (! Schema::hasColumn('bon_receptions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('received_by');
            }
            if (! Schema::hasColumn('bon_receptions', 'date_reception')) {
                $table->date('date_reception')->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('bon_receptions', 'nombre_colis')) {
                $table->integer('nombre_colis')->nullable()->after('date_reception');
            }
            if (! Schema::hasColumn('bon_receptions', 'observation')) {
                $table->text('observation')->nullable()->after('nombre_colis');
            }
            if (! Schema::hasColumn('bon_receptions', 'status')) {
                $table->string('status')->default('draft')->after('observation');
            }
            if (! Schema::hasColumn('bon_receptions', 'is_confirmed')) {
                $table->boolean('is_confirmed')->default(false)->after('status');
            }
            if (! Schema::hasColumn('bon_receptions', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('is_confirmed');
            }
            if (! Schema::hasColumn('bon_receptions', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            }
            if (! Schema::hasColumn('bon_receptions', 'attachments')) {
                $table->json('attachments')->nullable()->after('confirmed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            $table->dropColumn([
                'bonReceptionCode',
                'bon_commend_id',
                'bon_commend_date',
                'bon_livraison_numero',
                'bon_livraison_date',
                'facture_numero',
                'facture_date',
                'fournisseur_id',
                'received_by',
                'created_by',
                'date_reception',
                'nombre_colis',
                'observation',
                'status',
                'is_confirmed',
                'confirmed_at',
                'confirmed_by',
                'attachments',
            ]);
        });
    }
};
