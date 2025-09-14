<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $settings = DB::table('service_product_settings')->get();
    echo "=== CURRENT DATABASE STATE ===\n";
    foreach ($settings as $setting) {
        echo "Service ID: {$setting->service_id}\n";
        echo "Product ID: {$setting->product_id}\n";
        echo "Product Name: {$setting->product_name}\n";
        echo "Product Forme: " . ($setting->product_forme ?? 'NULL') . "\n";
        echo "Low Stock Threshold: {$setting->low_stock_threshold}\n";
        echo "Email Alerts: " . ($setting->email_alerts ? 'true' : 'false') . "\n";
        echo "---\n";
    }

    echo "\n=== WHAT YOUR FRONTEND SHOULD SEND ===\n";
    echo "To retrieve these settings, your frontend must include: product_forme = 'COMPRIME'\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
