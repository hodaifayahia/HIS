<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Checking foreign key constraints for inventories table:\n";
    
    $constraints = DB::select("
        SELECT CONSTRAINT_NAME, DELETE_RULE 
        FROM information_schema.REFERENTIAL_CONSTRAINTS 
        WHERE TABLE_NAME = 'inventories' 
        AND CONSTRAINT_SCHEMA = DATABASE()
    ");
    
    foreach ($constraints as $constraint) {
        echo "Constraint: {$constraint->CONSTRAINT_NAME}, Delete Rule: {$constraint->DELETE_RULE}\n";
    }
    
    echo "\nChecking table structure:\n";
    $createTable = DB::select("SHOW CREATE TABLE inventories");
    echo $createTable[0]->{'Create Table'} . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}