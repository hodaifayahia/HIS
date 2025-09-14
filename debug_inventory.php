<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$existing = DB::select('SELECT * FROM inventories WHERE product_id = 16 AND stockage_id = 2 AND batch_number = "100" AND serial_number = "100"');

echo 'Existing records with same details:' . PHP_EOL;
foreach($existing as $record) {
    echo 'ID: ' . $record->id . ', Expiry: ' . $record->expiry_date . PHP_EOL;
}

echo PHP_EOL . 'All inventory records for product 16 in stockage 2:' . PHP_EOL;
$all = DB::select('SELECT id, batch_number, serial_number, expiry_date FROM inventories WHERE product_id = 16 AND stockage_id = 2');
foreach($all as $record) {
    echo 'ID: ' . $record->id . ', Batch: ' . $record->batch_number . ', Serial: ' . $record->serial_number . ', Expiry: ' . $record->expiry_date . PHP_EOL;
}
