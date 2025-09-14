<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "Products with boite_de set:\n";
$products = Product::whereNotNull('boite_de')->take(10)->get(['id', 'name', 'boite_de']);
foreach ($products as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Boite_de: {$product->boite_de}\n";
}

echo "\nTotal products with boite_de: " . Product::whereNotNull('boite_de')->count() . "\n";
echo "Total products: " . Product::count() . "\n";
