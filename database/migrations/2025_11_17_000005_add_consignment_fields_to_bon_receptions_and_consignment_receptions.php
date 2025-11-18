<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add fields to consignment_receptions table (no foreign keys yet)
        if (Schema::hasTable('consignment_receptions')) {
            Schema::table('consignment_receptions', function (Blueprint $table) {
                if (! Schema::hasColumn('consignment_receptions', 'bon_reception_id')) {
                    $table->unsignedBigInteger('bon_reception_id')
                        ->nullable()
                        ->after('confirmed_by')
                        ->comment('Link to automatically created bon reception');
                }

                if (! Schema::hasColumn('consignment_receptions', 'bon_entree_id')) {
                    $table->unsignedBigInteger('bon_entree_id')
                        ->nullable()
                        ->after('bon_reception_id')
                        ->comment('Link to warehouse entry document');
                }
            });
        }

        // Then, add consignment tracking fields to bon_receptions table
        if (Schema::hasTable('bon_receptions')) {
            Schema::table('bon_receptions', function (Blueprint $table) {
                if (! Schema::hasColumn('bon_receptions', 'is_from_consignment')) {
                    $table->boolean('is_from_consignment')
                        ->default(false)
                        ->after('status')
                        ->comment('Indicates if this bon reception was created from consignment');
                }

                if (! Schema::hasColumn('bon_receptions', 'consignment_reception_id')) {
                    $table->unsignedBigInteger('consignment_reception_id')
                        ->nullable()
                        ->after('is_from_consignment')
                        ->comment('Link to originating consignment reception');
                }
            });
        }

        // Finally, add foreign key constraints (now that both tables have the columns)
        if (Schema::hasTable('consignment_receptions') && Schema::hasTable('bon_receptions')) {
            Schema::table('consignment_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('consignment_receptions', 'bon_reception_id') &&
                    ! $this->hasForeignKey('consignment_receptions', 'consignment_receptions_bon_reception_id_foreign')) {
                    $table->foreign('bon_reception_id', 'consignment_receptions_bon_reception_id_foreign')
                        ->references('id')
                        ->on('bon_receptions')
                        ->nullOnDelete();
                }

                if (Schema::hasColumn('consignment_receptions', 'bon_entree_id') &&
                    Schema::hasTable('bon_entrees') &&
                    ! $this->hasForeignKey('consignment_receptions', 'consignment_receptions_bon_entree_id_foreign')) {
                    $table->foreign('bon_entree_id', 'consignment_receptions_bon_entree_id_foreign')
                        ->references('id')
                        ->on('bon_entrees')
                        ->nullOnDelete();
                }
            });

            Schema::table('bon_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('bon_receptions', 'consignment_reception_id') &&
                    ! $this->hasForeignKey('bon_receptions', 'bon_receptions_consignment_reception_id_foreign')) {
                    $table->foreign('consignment_reception_id', 'bon_receptions_consignment_reception_id_foreign')
                        ->references('id')
                        ->on('consignment_receptions')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Check if a foreign key exists
     */
    private function hasForeignKey(string $table, string $foreignKeyName): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $result = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$dbName, $table, $foreignKeyName]);

        return count($result) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first
        if (Schema::hasTable('consignment_receptions')) {
            Schema::table('consignment_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('consignment_receptions', 'bon_entree_id')) {
                    try {
                        $table->dropForeign(['bon_entree_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist
                    }
                }

                if (Schema::hasColumn('consignment_receptions', 'bon_reception_id')) {
                    try {
                        $table->dropForeign(['bon_reception_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist
                    }
                }
            });
        }

        if (Schema::hasTable('bon_receptions')) {
            Schema::table('bon_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('bon_receptions', 'consignment_reception_id')) {
                    try {
                        $table->dropForeign(['consignment_reception_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist
                    }
                }
            });
        }

        // Then drop columns
        if (Schema::hasTable('bon_receptions')) {
            Schema::table('bon_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('bon_receptions', 'consignment_reception_id')) {
                    $table->dropColumn('consignment_reception_id');
                }

                if (Schema::hasColumn('bon_receptions', 'is_from_consignment')) {
                    $table->dropColumn('is_from_consignment');
                }
            });
        }

        if (Schema::hasTable('consignment_receptions')) {
            Schema::table('consignment_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('consignment_receptions', 'bon_entree_id')) {
                    $table->dropColumn('bon_entree_id');
                }

                if (Schema::hasColumn('consignment_receptions', 'bon_reception_id')) {
                    $table->dropColumn('bon_reception_id');
                }
            });
        }
    }
};
