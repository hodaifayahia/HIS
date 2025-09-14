<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $tables = DB::select("SHOW TABLES");
    echo "All tables in database:\n";
    foreach ($tables as $table) {
        $tableName = current($table);
        echo "- $tableName\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
