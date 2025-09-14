<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking all foreign key constraints in database...\n";

$result = DB::select('SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE = "FOREIGN KEY" AND TABLE_SCHEMA = DATABASE()');

if (empty($result)) {
    echo "No foreign key constraints found.\n";
} else {
    echo "Foreign key constraints found:\n";
    foreach ($result as $row) {
        echo "- {$row->TABLE_NAME}.{$row->CONSTRAINT_NAME} ({$row->CONSTRAINT_TYPE})\n";
    }
}

echo "\nChecking referential constraints...\n";

$referential = DB::select('SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_SCHEMA = DATABASE()');

if (empty($referential)) {
    echo "No referential constraints found.\n";
} else {
    echo "Referential constraints found:\n";
    foreach ($referential as $row) {
        echo "- {$row->TABLE_NAME}.{$row->CONSTRAINT_NAME} -> {$row->REFERENCED_TABLE_NAME}.{$row->REFERENCED_COLUMN_NAME}\n";
    }
}
