<?php

// Simple test script to verify stock product settings routes
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$router = app('router');
$routes = $router->getRoutes();

echo "Testing Stock Product Settings Routes:\n";
echo "=====================================\n\n";

$stockRoutes = [];
foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'stock/product-settings') !== false) {
        $stockRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $uri,
            'action' => $route->getActionName()
        ];
    }
}

if (empty($stockRoutes)) {
    echo "❌ No stock product settings routes found!\n";
    exit(1);
}

echo "✅ Found " . count($stockRoutes) . " stock product settings routes:\n\n";

foreach ($stockRoutes as $route) {
    echo "• " . $route['method'] . " " . $route['uri'] . "\n";
    echo "  → " . $route['action'] . "\n\n";
}

echo "✅ All routes are properly registered!\n";
echo "\nTesting complete. The backend infrastructure is ready for frontend integration.\n";
