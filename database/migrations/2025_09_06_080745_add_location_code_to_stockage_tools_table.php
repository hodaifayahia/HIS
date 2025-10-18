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
        Schema::table('stockage_tools', function (Blueprint $table) {
            if (! Schema::hasColumn('stockage_tools', 'location_code')) {
                $table->string('location_code')->nullable()->after('shelf_level');
            }
        });

        // Generate location codes for existing records
        $tools = DB::table('stockage_tools')
            ->join('stockages', 'stockage_tools.stockage_id', '=', 'stockages.id')
            ->join('services', 'stockages.service_id', '=', 'services.id')
            ->select('stockage_tools.*', 'stockages.location_code as stockage_location_code', 'services.service_abv')
            ->get();

        foreach ($tools as $tool) {
            $locationCode = $this->generateLocationCode($tool);
            DB::table('stockage_tools')
                ->where('id', $tool->id)
                ->update(['location_code' => $locationCode]);
        }

        // Add unique constraint after populating data
        Schema::table('stockage_tools', function (Blueprint $table) {
            if (! Schema::hasColumn('stockage_tools', 'location_code')) {
                $table->unique('location_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stockage_tools', function (Blueprint $table) {
            $table->dropUnique(['location_code']);
            $table->dropColumn('location_code');
        });
    }

    /**
     * Generate location code based on tool type and data.
     */
    private function generateLocationCode($tool): string
    {
        $base = $tool->service_abv.$tool->stockage_location_code.'-'.$tool->tool_type.$tool->tool_number;

        if ($tool->tool_type === 'RY' && $tool->block && $tool->shelf_level) {
            $base .= '-'.$tool->block.$tool->shelf_level;
        }

        return $base;
    }
};
