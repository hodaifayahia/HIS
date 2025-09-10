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
        Schema::table('caisse_sessions', function (Blueprint $table) {

            $table->foreignId('open_by')->constrained('users')->onDelete('cascade');
              $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('coffre_id_source')->nullable()->constrained('coffres')->onDelete('cascade');
            $table->foreignId('coffre_id_destination')->nullable()->constrained('coffres')->onDelete('cascade');
            // Indexes
            $table->index(['caisse_id', 'status']);
            $table->index(['user_id', 'ouverture_at']);
            $table->index(['status', 'ouverture_at']);


            $table->decimal('total_cash_counted', 15, 2)->nullable()->after('closing_amount');
            $table->decimal('cash_difference', 15, 2)->nullable()->after('total_cash_counted'); // Difference between closing_amount and total_cash_counted
            // Unique constraint: only one open session per caisse
            $table->unique(['caisse_id', 'status'], 'unique_open_session')
                  ->where('status', 'open');
          $table->text('opening_notes')->nullable();
          $table->text('closing_notes')->nullable();
    });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisse_sessions', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['caisse_id', 'status']);
            $table->dropIndex(['user_id', ]);
            $table->dropIndex(['status']);
            $table->dropUnique('unique_open_session');
            $table->dropColumn('new_field');
            $table->dropColumn('total_cash_counted');
            $table->dropColumn('cash_difference');
            $table->dropForeign(['open_by']);
            $table->dropForeign(['closed_by']);
            $table->dropForeign(['coffre_id_source']);
            $table->dropForeign(['coffre_id_destination']);
            $table->dropColumn(['open_by', 'closed_by', 'coffre_id']);
        });
    }
};