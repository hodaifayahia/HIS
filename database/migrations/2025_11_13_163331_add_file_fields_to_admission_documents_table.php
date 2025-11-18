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
        Schema::table('admission_documents', function (Blueprint $table) {
            // Only add columns that don't exist yet
            if (! Schema::hasColumn('admission_documents', 'file_name')) {
                $table->string('file_name')->nullable();
            }
            if (! Schema::hasColumn('admission_documents', 'file_type')) {
                $table->string('file_type')->nullable();
            }
            if (! Schema::hasColumn('admission_documents', 'file_size')) {
                $table->integer('file_size')->nullable();
            }
            if (! Schema::hasColumn('admission_documents', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('admission_documents', 'verified')) {
                $table->boolean('verified')->default(false);
            }
            if (! Schema::hasColumn('admission_documents', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('admission_documents', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_documents', function (Blueprint $table) {
            $columns = ['file_name', 'file_type', 'file_size', 'description', 'verified', 'verified_by', 'uploaded_by'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('admission_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
