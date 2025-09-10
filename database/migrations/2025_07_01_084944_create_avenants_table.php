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
       Schema::create('avenants', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('convention_id')->constrained('conventions')->onDelete('cascade'); // Not null foreign key
            $table->text('description')->nullable(); // Nullable text field
            $table->timestamp('activation_datetime')->comment('The exact moment the changes take effect'); // Not null
            $table->string('status')->comment('draft, pending-approval, approved, active, archived'); // Not null
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade'); // Not null foreign key
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avenants');
    }
};
