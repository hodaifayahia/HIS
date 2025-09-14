<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StockageTool;
use App\Models\Stockage;
use Illuminate\Http\Request;

// Test creating a stockage tool
try {
    $stockage = Stockage::first();
    if (!$stockage) {
        echo "No stockage found in database\n";
        exit(1);
    }

    echo "Testing StockageTool creation...\n";
    echo "Stockage ID: {$stockage->id}\n";

    // Create a request
    $request = new Request([
        'tool_type' => 'RY',
        'tool_number' => 1,
        'block' => 'A',
        'shelf_level' => 1
    ]);

    // Test the controller
    $controller = app('App\Http\Controllers\Stock\StockageToolController');
    $response = $controller->store($request, $stockage->id);

    // Test retrieving tools
    echo "\nTesting StockageTool retrieval...\n";
    $request = new Request();
    $response = $controller->index($request, $stockage->id);
    echo "Tools Response: " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
