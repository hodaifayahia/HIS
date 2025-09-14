<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $count = DB::table('service_product_settings')->count();
    echo "Total records in service_product_settings: $count\n\n";

    if ($count > 0) {
        $settings = DB::table('service_product_settings')->get();
        echo "All records:\n";
        foreach ($settings as $index => $setting) {
            echo ($index + 1) . ". ID: {$setting->id}\n";
            echo "   Service ID: {$setting->service_id}\n";
            echo "   Product ID: {$setting->product_id}\n";
            echo "   Product Name: {$setting->product_name}\n";
            echo "   Product Forme: " . ($setting->product_forme ?? 'NULL') . "\n";
            echo "   Low Stock Threshold: {$setting->low_stock_threshold}\n";
            echo "   Email Alerts: " . ($setting->email_alerts ? 'true' : 'false') . "\n";
            echo "   Created: {$setting->created_at}\n";
            echo "   Updated: {$setting->updated_at}\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
