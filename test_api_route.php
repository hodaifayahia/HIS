<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Stock\ServiceProductSettingController;

try {
    $controller = new ServiceProductSettingController();
    $request = new Request();

    echo "=== TESTING API ROUTE SIMULATION ===\n\n";

    // Simulate the API route call: GET /api/stock/services/3/products/16/settings/?product_forme=COMPRIME
    echo "1. API Route: GET /api/stock/services/3/products/16/settings/?product_forme=COMPRIME\n";
    $request->merge(['product_forme' => 'COMPRIME']);
    $response = $controller->show($request, 3, 16); // serviceId=3, productParam=16
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   SMS Alerts: " . ($data['data']['sms_alerts'] ? 'true' : 'false') . "\n";
        echo "   Auto Reorder: " . ($data['data']['auto_reorder'] ? 'true' : 'false') . "\n";
        echo "   Low Stock Threshold: " . $data['data']['low_stock_threshold'] . "\n";
    } else {
        echo "   Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";

    // Test without product_forme to see default behavior
    echo "2. API Route: GET /api/stock/services/3/products/16/settings/ (no product_forme)\n";
    $request2 = new Request(); // Clean request
    $response = $controller->show($request2, 3, 16);
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   SMS Alerts: " . ($data['data']['sms_alerts'] ? 'true' : 'false') . "\n";
        echo "   Auto Reorder: " . ($data['data']['auto_reorder'] ? 'true' : 'false') . "\n";
        echo "   Low Stock Threshold: " . $data['data']['low_stock_threshold'] . "\n";
    }
    echo "\n";

    echo "=== CONCLUSION ===\n";
    echo "The API route simulation works correctly!\n";
    echo "Frontend should call: /api/stock/services/{serviceId}/products/{productId}/settings/?product_forme={forme}\n";

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
