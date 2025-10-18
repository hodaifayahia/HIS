<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('caisse_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // The cashier
            $table->timestamp('opened_at')->useCurrent();
            $table->decimal('theoretical_amount', 12, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_amount', 15, 2);
            $table->decimal('closing_amount', 15, 2)->nullable();
            $table->decimal('expected_closing_amount', 15, 2)->nullable(); // Calculated from transactions
            $table->string('status')->default('open'); // e.g., open, closed, reconciled
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('caisse_sessions');
    Schema::enableForeignKeyConstraints();
}

};
