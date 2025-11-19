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
        Schema::table('bon_receptions', function (Blueprint $table) {
            // Add missing columns
            if (! Schema::hasColumn('bon_receptions', 'bonReceptionCode')) {
                $table->string('bonReceptionCode')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('bon_receptions', 'fournisseur_id')) {
                $table->unsignedBigInteger('fournisseur_id')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'date_reception')) {
                $table->date('date_reception')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'bon_livraison_numero')) {
                $table->string('bon_livraison_numero')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'bon_livraison_date')) {
                $table->date('bon_livraison_date')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'facture_numero')) {
                $table->string('facture_numero')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'facture_date')) {
                $table->date('facture_date')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'nombre_colis')) {
                $table->integer('nombre_colis')->default(0);
            }

            if (! Schema::hasColumn('bon_receptions', 'observation')) {
                $table->text('observation')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'status')) {
                $table->enum('status', ['pending', 'completed', 'canceled', 'rejected'])->default('pending');
            }

            if (! Schema::hasColumn('bon_receptions', 'attachments')) {
                $table->json('attachments')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }

            if (! Schema::hasColumn('bon_receptions', 'is_confirmed')) {
                $table->boolean('is_confirmed')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_receptions', function (Blueprint $table) {
            // Drop foreign keys
            try {
                $table->dropForeign(['fournisseur_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            // Drop columns
            $columnsToDropIfExist = [
                'bonReceptionCode',
                'fournisseur_id',
                'date_reception',
                'bon_livraison_numero',
                'bon_livraison_date',
                'facture_numero',
                'facture_date',
                'nombre_colis',
                'observation',
                'status',
                'attachments',
                'created_by',
                'confirmed_by',
                'confirmed_at',
                'is_confirmed',
            ];

            foreach ($columnsToDropIfExist as $column) {
                if (Schema::hasColumn('bon_receptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
