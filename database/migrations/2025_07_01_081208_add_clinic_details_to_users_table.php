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
        Schema::table('users', function (Blueprint $table) {
        // Add new columns 
      // $table->string('internal_id_number')->unique()->after('id'); // Add after 'id' for logical ordering
       // If 'password' exists, consider if this replaces it or is a new field.
             $table->string('job_title')->nullable();
             $table->string('account_status')->nullable()->after('job_title');
             $table->string('professional_license_number')->nullable()->after('account_status');
             $table->json('fee_account_details')->nullable()->after('professional_license_number');
             $table->decimal('personal_discount_ceiling', 10, 2)->nullable()->after('fee_account_details');
             // Add foreign keys. Ensure 'services' table exists before running this migration.
             // Temporarily disable foreign key checks if you need to add self-referencing foreign keys
             // or if the referenced table is created in a later migration.
             // Schema::enableForeignKeyConstraints(); // Uncomment if you disable them elsewher
             $table->foreignId('service_id')
                   ->nullable()
                   ->constrained('services')
                   ->onDelete('set null')
                   ->after('personal_discount_ceiling');
             // For self-referencing foreign key 'manager_id', ensure it points to the 'id' of the 'users' table itself.
             $table->foreignId('manager_id')
                   ->nullable()
                   ->constrained('users')
                   ->onDelete('set null')
                   ->after('service_id');
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
