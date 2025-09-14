<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Stock\ServiceProductSettingController;

try {
    $controller = new ServiceProductSettingController();
    $request = new Request();

    echo "=== Testing different scenarios for retrieving product settings ===\n\n";

    // Scenario 1: Using API route format (serviceId=3, productId=16, no forme)
    echo "1. API Route - Service: 3, Product: 16 (no forme):\n";
    $response = $controller->show($request, 3, 16);
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   Product Forme: " . ($data['data']['product_forme'] ?? 'NULL') . "\n";
        echo "   Low Stock Threshold: " . ($data['data']['low_stock_threshold'] ?? 'N/A') . "\n";
        echo "   Email Alerts: " . ($data['data']['email_alerts'] ? 'true' : 'false') . "\n";
    }
    echo "\n";

    // Scenario 2: Using API route format with forme parameter
    echo "2. API Route - Service: 3, Product: 16, Forme: COMPRIME:\n";
    $response = $controller->show($request, 3, 16, 'COMPRIME');
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   Product Forme: " . ($data['data']['product_forme'] ?? 'NULL') . "\n";
        echo "   Low Stock Threshold: " . ($data['data']['low_stock_threshold'] ?? 'N/A') . "\n";
        echo "   Email Alerts: " . ($data['data']['email_alerts'] ? 'true' : 'false') . "\n";
    }
    echo "\n";

    // Scenario 3: Using web route format (serviceId=3, productName=Pinicline A, no forme)
    echo "3. Web Route - Service: 3, Product: 'Pinicline A' (no forme):\n";
    $response = $controller->show($request, 3, 'Pinicline A');
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   Product Forme: " . ($data['data']['product_forme'] ?? 'NULL') . "\n";
        echo "   Low Stock Threshold: " . ($data['data']['low_stock_threshold'] ?? 'N/A') . "\n";
        echo "   Email Alerts: " . ($data['data']['email_alerts'] ? 'true' : 'false') . "\n";
    }
    echo "\n";

    // Scenario 4: Using web route format with forme parameter
    echo "4. Web Route - Service: 3, Product: 'Pinicline A', Forme: COMPRIME:\n";
    $response = $controller->show($request, 3, 'Pinicline A', 'COMPRIME');
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   Product Forme: " . ($data['data']['product_forme'] ?? 'NULL') . "\n";
        echo "   Low Stock Threshold: " . ($data['data']['low_stock_threshold'] ?? 'N/A') . "\n";
        echo "   Email Alerts: " . ($data['data']['email_alerts'] ? 'true' : 'false') . "\n";
    }
    echo "\n";

    // Scenario 5: Test with query parameter for forme
    echo "5. API Route with query param - Service: 3, Product: 16, Forme via query:\n";
    $request->merge(['product_forme' => 'COMPRIME']);
    $response = $controller->show($request, 3, 16);
    $data = json_decode($response->getContent(), true);
    echo "   Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "   Product Forme: " . ($data['data']['product_forme'] ?? 'NULL') . "\n";
        echo "   Low Stock Threshold: " . ($data['data']['low_stock_threshold'] ?? 'N/A') . "\n";
        echo "   Email Alerts: " . ($data['data']['email_alerts'] ? 'true' : 'false') . "\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
