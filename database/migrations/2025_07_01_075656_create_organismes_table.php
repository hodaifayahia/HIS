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
       Schema::create('organismes', function (Blueprint $table) {
           $table->id(); // Auto-incrementing primary key

            // Company General Information
            $table->string('name');
            $table->string('legal_form')->nullable(); // EURL, SARL, SPA, etc.
            $table->string('trade_register_number')->unique()->nullable();
            $table->string('tax_id_nif')->unique()->nullable();
            $table->string('statistical_id')->unique()->nullable();
            $table->string('article_number')->nullable();
            $table->string('wilaya')->nullable(); // ADRAR, CHLEF, etc.
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); // For geographic coordinates
            $table->decimal('longitude', 10, 7)->nullable(); // For geographic coordinates

            // Invoice/Credit Note Related
            $table->string('initial_invoice_number')->nullable();
            $table->string('initial_credit_note_number')->nullable();

            // Additional fields (based on common company details)
            $table->string('logo_url')->nullable(); // URL or path to the company logo
            $table->string('profile_image_url')->nullable(); // URL or path to a profile image
            $table->text('description')->nullable(); // Longer text description
            $table->string('industry')->nullable(); // Sector d'activité
            $table->date('creation_date')->nullable(); // Date of company creation
            $table->unsignedInteger('number_of_employees')->nullable(); // Number of employees

            $table->timestamps(); // Adds `created_at` and `updated_at` columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organismes');
    }
};
