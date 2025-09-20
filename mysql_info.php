<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== MySQL Configuration Check ===\n\n";
    
    $version = DB::select('SELECT @@version as version')[0]->version;
    $fkChecks = DB::select('SELECT @@foreign_key_checks as fk_checks')[0]->fk_checks;
    
    echo "MySQL Version: {$version}\n";
    echo "Foreign Key Checks: " . ($fkChecks ? 'ON' : 'OFF') . "\n\n";
    
    // Check if there are any triggers on the tables
    echo "Checking for triggers on products table:\n";
    $triggers = DB::select("SHOW TRIGGERS LIKE 'products'");
    if (empty($triggers)) {
        echo "  No triggers found\n";
    } else {
        foreach ($triggers as $trigger) {
            echo "  Trigger: {$trigger->Trigger}, Event: {$trigger->Event}, Timing: {$trigger->Timing}\n";
        }
    }
    
    echo "\nChecking for triggers on inventories table:\n";
    $triggers = DB::select("SHOW TRIGGERS LIKE 'inventories'");
    if (empty($triggers)) {
        echo "  No triggers found\n";
    } else {
        foreach ($triggers as $trigger) {
            echo "  Trigger: {$trigger->Trigger}, Event: {$trigger->Event}, Timing: {$trigger->Timing}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}