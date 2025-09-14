<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking foreign key constraints referencing inventories table...\n";

$constraints = DB::select('SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = "inventories"');

if (empty($constraints)) {
    echo "No foreign key constraints found referencing inventories table.\n";
} else {
    echo "Foreign key constraints found:\n";
    foreach ($constraints as $constraint) {
        echo "- {$constraint->CONSTRAINT_NAME}: {$constraint->TABLE_NAME}.{$constraint->COLUMN_NAME} -> {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME}\n";
    }
}

echo "\nChecking foreign keys from inventories table...\n";

$fkConstraints = DB::select('SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = "inventories" AND REFERENCED_TABLE_NAME IS NOT NULL');

if (empty($fkConstraints)) {
    echo "No foreign key constraints found from inventories table.\n";
} else {
    echo "Foreign key constraints from inventories table:\n";
    foreach ($fkConstraints as $constraint) {
        echo "- {$constraint->CONSTRAINT_NAME}: {$constraint->TABLE_NAME}.{$constraint->COLUMN_NAME} -> {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME}\n";
    }
}
