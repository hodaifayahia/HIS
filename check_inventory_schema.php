<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking inventories table indexes...\n";

$indexes = DB::select('SHOW INDEX FROM inventories');

echo "Indexes found:\n";
foreach ($indexes as $index) {
    echo "- {$index->Key_name} on {$index->Column_name} (" . ($index->Non_unique ? 'Not Unique' : 'Unique') . ")\n";
}

echo "\nChecking for unique constraints...\n";

$uniqueIndexes = DB::select("SHOW INDEX FROM inventories WHERE Non_unique = 0");

echo "Unique indexes:\n";
foreach ($uniqueIndexes as $index) {
    echo "- {$index->Key_name} on {$index->Column_name}\n";
}
