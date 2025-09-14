<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement('DROP TABLE IF EXISTS service_product_settings');
    echo "Table dropped successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
