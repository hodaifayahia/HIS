<?php

// Bootstrap Laravel for testing
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Reception\ficheNavetteItem;

try {
    $item = ficheNavetteItem::find(104);
    if ($item) {
        $item->remaining_amount = 100;
        $item->paid_amount = 0;
        $item->save();
        echo "Updated item 104: remaining_amount = 100, paid_amount = 0\n";
    } else {
        echo "Item 104 not found\n";
    }
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
