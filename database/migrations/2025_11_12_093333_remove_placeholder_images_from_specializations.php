<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove placeholder images from specializations table
        DB::table('specializations')
            ->where('photo', 'like', 'https://via.placeholder.com%')
            ->update(['photo' => null]);

        // Remove placeholder images from services table if it has image_url
        if (Schema::hasColumn('services', 'image_url')) {
            DB::table('services')
                ->where('image_url', 'like', 'https://via.placeholder.com%')
                ->update(['image_url' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data cleanup migration, no need to restore placeholder URLs
    }
};
