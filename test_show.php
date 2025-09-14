<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Stock\ServiceProductSettingController;

try {
    // Test the show method with the stored data
    $controller = new ServiceProductSettingController();
    $request = new Request();

    // Test with service_id=3, product_id=16, product_forme=COMPRIME
    // This simulates calling: GET /api/stock/services/3/products/16/settings/?product_forme=COMPRIME
    $response = $controller->show($request, 3, 16, 'COMPRIME');

    $responseData = json_decode($response->getContent(), true);

    echo "Test Results:\n";
    echo "Success: " . ($responseData['success'] ? 'YES' : 'NO') . "\n";

    if ($responseData['success'] && isset($responseData['data'])) {
        $data = $responseData['data'];
        echo "Product Name: " . ($data['product_name'] ?? 'N/A') . "\n";
        echo "Product Forme: " . ($data['product_forme'] ?? 'NULL') . "\n";
        echo "Low Stock Threshold: " . ($data['low_stock_threshold'] ?? 'N/A') . "\n";
        echo "Email Alerts: " . ($data['email_alerts'] ? 'YES' : 'NO') . "\n";
    } else {
        echo "Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
