<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Validator;

$rules = [
    'items' => 'required|array|min:1',
    'items.*.product_id' => 'nullable|exists:products,id|required_without:items.*.pharmacy_product_id',
    'items.*.pharmacy_product_id' => 'nullable|exists:pharmacy_products,id|required_without:items.*.product_id',
    'items.*.quantity' => 'required|integer|min:1',
];

function testPayload($payload, $name)
{
    global $rules;
    echo "Testing: $name\n";
    $v = Validator::make($payload, $rules);
    if ($v->fails()) {
        echo " Validation fails: \n".json_encode($v->errors()->all())."\n\n";

        return;
    }
    echo " Validation passes.\n";
    // manual check to forbid both ids
    foreach ($payload['items'] as $i => $item) {
        $hasProduct = ! empty($item['product_id']);
        $hasPharmacy = ! empty($item['pharmacy_product_id']);
        if ($hasProduct && $hasPharmacy) {
            echo " Manual check: Item $i has both product_id and pharmacy_product_id -> FAIL\n\n";

            return;
        }
    }
    echo " Manual check: PASS\n\n";
}

// Prepare payloads
$firstProduct = DB::table('products')->select('id')->first();
$firstPharmacyProduct = DB::table('pharmacy_products')->select('id')->first();

$pId = $firstProduct ? $firstProduct->id : 1;
$phId = $firstPharmacyProduct ? $firstPharmacyProduct->id : 1;

$payloads = [
    'product_only' => ['items' => [['product_id' => $pId, 'quantity' => 5]]],
    'pharmacy_only' => ['items' => [['pharmacy_product_id' => $phId, 'quantity' => 2]]],
    'both_ids' => ['items' => [['product_id' => $pId, 'pharmacy_product_id' => $phId, 'quantity' => 1]]],
    'none_ids' => ['items' => [['quantity' => 1]]],
];

foreach ($payloads as $name => $pl) {
    testPayload($pl, $name);
}

echo "Done\n";
