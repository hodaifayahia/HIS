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
        Schema::table('product_global_settings', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('product_global_settings', 'product_id')) {
                $table->unsignedBigInteger('product_id')->after('id');
            }
            if (!Schema::hasColumn('product_global_settings', 'setting_key')) {
                $table->string('setting_key');
            }
            if (!Schema::hasColumn('product_global_settings', 'setting_value')) {
                $table->json('setting_value');
            }
            if (!Schema::hasColumn('product_global_settings', 'description')) {
                $table->text('description')->nullable();
            }
        });
        
        // Add constraints in a separate schema call to avoid conflicts
        try {
            Schema::table('product_global_settings', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        } catch (\Exception $e) {
             // Foreign key might already exist, ignore
         }
         
         try {
             Schema::table('product_global_settings', function (Blueprint $table) {
                 $table->unique(['product_id', 'setting_key']);
             });
         } catch (\Exception $e) {
             // Unique constraint might already exist, ignore
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_global_settings', function (Blueprint $table) {
            // Drop foreign key if it exists
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('product_global_settings');
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('product_id', $foreignKey->getColumns())) {
                    $table->dropForeign(['product_id']);
                    break;
                }
            }
            
            // Drop unique constraint if it exists
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('product_global_settings');
            foreach ($indexes as $index) {
                if ($index->isUnique() && in_array('product_id', $index->getColumns()) && in_array('setting_key', $index->getColumns())) {
                    $table->dropUnique(['product_id', 'setting_key']);
                    break;
                }
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('product_global_settings', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('product_global_settings', 'setting_value')) {
                $table->dropColumn('setting_value');
            }
            if (Schema::hasColumn('product_global_settings', 'setting_key')) {
                $table->dropColumn('setting_key');
            }
            if (Schema::hasColumn('product_global_settings', 'product_id')) {
                $table->dropColumn('product_id');
            }
        });
    }
};
