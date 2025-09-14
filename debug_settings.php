<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $settings = DB::table('service_product_settings')->get();
    echo "Current records in service_product_settings:\n";
    foreach ($settings as $setting) {
        echo "- ID: {$setting->id}, Service: {$setting->service_id}, Product: {$setting->product_id} ({$setting->product_name}), Forme: " . ($setting->product_forme ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
