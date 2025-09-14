<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $count = DB::table('service_product_settings')->count();
    echo "Total records in service_product_settings: $count\n";

    if ($count > 0) {
        $settings = DB::table('service_product_settings')->get();
        echo "\nAll records:\n";
        foreach ($settings as $setting) {
            echo "- ID: {$setting->id}, Service: {$setting->service_id}, Product: {$setting->product_id} ({$setting->product_name}), Forme: " . ($setting->product_forme ?? 'NULL') . "\n";
            echo "  Low Stock Threshold: {$setting->low_stock_threshold}, Email Alerts: {$setting->email_alerts}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
