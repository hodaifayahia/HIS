<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking the unique index details...\n";

$result = DB::select('SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "inventories" AND INDEX_NAME = "inventories_product_id_stockage_id_unique"');

if (empty($result)) {
    echo "No index found with that name.\n";
} else {
    echo "Index details:\n";
    foreach ($result as $row) {
        echo "- Column: {$row->COLUMN_NAME}, Seq: {$row->SEQ_IN_INDEX}, Non_unique: {$row->NON_UNIQUE}\n";
    }
}

echo "\nChecking for any foreign keys that might reference this index...\n";

$fkResult = DB::select('SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = "inventories" AND TABLE_SCHEMA = DATABASE()');

if (empty($fkResult)) {
    echo "No foreign keys reference the inventories table.\n";
} else {
    echo "Foreign keys referencing inventories:\n";
    foreach ($fkResult as $row) {
        echo "- {$row->TABLE_NAME}.{$row->COLUMN_NAME} -> inventories.{$row->REFERENCED_COLUMN_NAME}\n";
    }
}
