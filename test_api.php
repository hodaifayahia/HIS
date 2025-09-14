<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Stock\InventoryController;
use Illuminate\Http\Request;

try {
    echo "Testing API call for service stock...\n";

    // Create a mock request
    $request = new Request();
    $request->merge(['service_id' => 3]); // Test with service_id=3 that the user is trying

    // Create controller instance and call the method directly
    $controller = new InventoryController();
    $response = $controller->getServiceStock($request);

    echo "Response type: " . gettype($response) . "\n";

    if (method_exists($response, 'getData')) {
        $data = $response->getData(true); // Get as array
        echo "Response data:\n";
        print_r($data);
    } else {
        echo "Response content: " . $response . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
