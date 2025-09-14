<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$indexes = DB::select('SHOW INDEX FROM inventories');

echo 'Indexes on inventories table:' . PHP_EOL;
foreach($indexes as $index) {
    echo '- ' . $index->Key_name . ' (' . $index->Column_name . ') - ' . ($index->Non_unique ? 'Not Unique' : 'Unique') . PHP_EOL;
}
