<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\StockageTool;

$tools = StockageTool::with('stockage.service')->get();
$updated = 0;

foreach ($tools as $tool) {
    try {
        $tool->location_code = $tool->generateLocationCode();
        $tool->save();
        $updated++;
        echo "Updated tool ID {$tool->id}: {$tool->location_code}\n";
    } catch (Exception $e) {
        echo "Error updating tool ID {$tool->id}: {$e->getMessage()}\n";
    }
}

echo "Total tools updated: {$updated}\n";
