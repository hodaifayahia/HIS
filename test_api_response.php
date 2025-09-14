<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

// Test the API response
$products = Product::with(['inventories' => function ($query) {
    $query->with('stockage');
}])->take(5)->get();

echo "Sample products from database:\n";
foreach ($products as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Boite_de: " . ($product->boite_de ?? 'null') . "\n";
}

// Test the controller response
$controller = new \App\Http\Controllers\Stock\ProductController();
$request = new \Illuminate\Http\Request();
$request->merge(['per_page' => 5]);
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

echo "\nAPI Response sample:\n";
if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $product) {
        echo "ID: {$product['id']}, Name: {$product['name']}, Boite_de: " . ($product['boite_de'] ?? 'null') . "\n";
    }
}
