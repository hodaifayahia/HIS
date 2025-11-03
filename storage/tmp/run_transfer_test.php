<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $controller = new \App\Http\Controllers\BonEntreeController();
    $response = $controller->transfer(32);

    // Controller returns a JsonResponse; extract data
    if (method_exists($response, 'getData')) {
        $data = $response->getData(true);
    } else {
        $data = (string) $response;
    }

    echo json_encode(['status' => 'ok', 'response' => $data], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], JSON_PRETTY_PRINT);
}
