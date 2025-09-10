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
        Schema::table('prestation_pricing', function (Blueprint $table) {
            // IMPORTANT: If 'prestation_id' is also a standalone foreign key to the 'prestations' table,
            // you might need to drop that first IF the unique index was covering it.
            // However, the error suggests the unique index itself is used.

            // Step 1: Drop any foreign key constraints that might rely on this unique index.
            // This is the most common cause of "Cannot drop index: needed in a foreign key constraint".
            // You MUST replace 'fk_prestation_pricing_on_something' with the actual name of your foreign key constraint.
            // If you don't know the name, you'll have to find it in your database schema or previous migrations.
            // Example: $table->dropForeign(['annex_id']); // If annex_id has a FK
            // Example: $table->dropForeign(['prestation_id']); // If prestation_id has a FK
            // It's more likely a compound foreign key on another table, or MySQL internally bound it.

            // If the error persists, it means the unique index itself might be implicitly acting as a foreign key target.
            // In such a case, the foreign key might be unnamed or auto-generated.
            // Laravel's schema builder might allow dropping the unique constraint directly if it's the FK's "source".
            // Let's try to remove the unique key and then add the new one.
            // For MySQL, dropping the unique index might implicitly drop an auto-created foreign key index.

            // Try to explicitly drop the unique index.
            // Laravel's dropUnique should handle removing associated indexes.
            // $table->dropUnique('prestation_pricing_prestation_id_annex_id_unique');

            // // Step 2: Add the new unique index that includes 'avenant_id'
            // // This ensures uniqueness per prestation, per annex, per avenant.
            // $table->unique(['prestation_id', 'annex_id', 'avenant_id'], 'prestation_pricing_prestation_id_annex_id_avenant_id_unique');

            // Step 3 (Conditional): If you dropped any explicit foreign keys in Step 1, re-add them here.
            // For example:
            // $table->foreign('annex_id')->references('id')->on('annexes')->onDelete('cascade');
            // $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestation_pricing', function (Blueprint $table) {
            // Drop the new unique index first
            $table->dropUnique('prestation_pricing_prestation_id_annex_id_avenant_id_unique');

            // Re-add the old unique index (if it wasn't the FK target)
            $table->unique(['prestation_id', 'annex_id'], 'prestation_pricing_prestation_id_annex_id_unique');

            // Re-add any foreign keys that were dropped in the 'up' method, pointing to the original unique index
            // For example:
            // $table->foreign('annex_id')->references('id')->on('annexes')->onDelete('cascade');
            // $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('cascade');
        });
    }
};