<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $count = DB::table('service_product_settings')->count();
    echo "Records in service_product_settings: $count\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
