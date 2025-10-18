<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Test the nursing products API functionality
function testNursingProducts()
{
    echo "=== Testing Nursing Products Functionality ===\n\n";

    // Test 1: Check User Specializations
    echo "1. Testing User Specializations Relationship:\n";
    try {
        $userSpecializations = DB::table('user_specializations as us')
            ->join('specializations as s', 'us.specialization_id', '=', 's.id')
            ->join('services as srv', 's.service_id', '=', 'srv.id')
            ->where('us.user_id', 1) // Test with user ID 1
            ->where('us.is_active', true)
            ->select(
                'us.*',
                's.name as specialization_name',
                's.service_id',
                'srv.name as service_name'
            )
            ->get();

        echo 'Found '.count($userSpecializations)." active specializations:\n";
        foreach ($userSpecializations as $spec) {
            echo "  - {$spec->specialization_name} -> Service: {$spec->service_name} (ID: {$spec->service_id})\n";
        }
        echo "\n";

        $serviceIds = $userSpecializations->pluck('service_id')->unique()->toArray();
        echo "User's Service IDs: ".implode(', ', $serviceIds)."\n\n";
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n\n";
    }

    // Test 2: Check Stock Products for Services
    echo "2. Testing Stock Products with Service Filter:\n";
    try {
        $serviceIds = [1, 2, 3]; // Example service IDs
        $stockProducts = DB::table('products as p')
            ->join('inventories as i', 'p.id', '=', 'i.product_id')
            ->join('locations as l', 'i.location_id', '=', 'l.id')
            ->leftJoin('services as s', 'l.service_id', '=', 's.id')
            ->whereIn('l.service_id', $serviceIds)
            ->where('p.is_active', true)
            ->select(
                'p.id',
                'p.name',
                'p.code_interne',
                'l.name as location_name',
                's.name as service_name',
                'i.quantity',
                'i.unit'
            )
            ->get();

        echo 'Found '.count($stockProducts)." stock products:\n";
        foreach ($stockProducts->take(5) as $product) {
            echo "  - {$product->name} ({$product->code_interne}) - {$product->service_name} - Qty: {$product->quantity} {$product->unit}\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n\n";
    }

    // Test 3: Check Pharmacy Products for Services
    echo "3. Testing Pharmacy Products with Service Filter:\n";
    try {
        $serviceIds = [1, 2, 3]; // Example service IDs
        $pharmacyProducts = DB::table('pharmacy_products as pp')
            ->join('pharmacy_inventories as pi', 'pp.id', '=', 'pi.pharmacy_product_id')
            ->join('locations as l', 'pi.location_id', '=', 'l.id')
            ->leftJoin('services as s', 'l.service_id', '=', 's.id')
            ->whereIn('l.service_id', $serviceIds)
            ->where('pp.is_active', true)
            ->select(
                'pp.id',
                'pp.name',
                'pp.dci',
                'pp.dosage',
                'pp.forme',
                'l.name as location_name',
                's.name as service_name',
                'pi.quantity_boxes',
                'pi.quantity_units'
            )
            ->get();

        echo 'Found '.count($pharmacyProducts)." pharmacy products:\n";
        foreach ($pharmacyProducts->take(5) as $product) {
            echo "  - {$product->name} (DCI: {$product->dci}) - {$product->service_name}\n";
            echo "    Boxes: {$product->quantity_boxes}, Units: {$product->quantity_units}\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n\n";
    }

    // Test 4: Check Product Categories
    echo "4. Testing Product Categories:\n";
    try {
        // Stock categories
        $stockCategories = DB::table('products')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        echo 'Stock Product Categories ('.count($stockCategories)."):\n";
        foreach ($stockCategories->take(10) as $category) {
            echo "  - $category\n";
        }

        // Pharmacy categories (using forme field)
        $pharmacyFormes = DB::table('pharmacy_products')
            ->whereNotNull('forme')
            ->where('forme', '!=', '')
            ->where('is_active', true)
            ->distinct()
            ->pluck('forme')
            ->sort()
            ->values();

        echo "\nPharmacy Product Formes (".count($pharmacyFormes)."):\n";
        foreach ($pharmacyFormes->take(10) as $forme) {
            echo "  - $forme\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n\n";
    }

    // Test 5: Simulate Combined Results
    echo "5. Testing Combined Product Results:\n";
    try {
        $combinedResults = [];

        // Add some example combined results
        $combinedResults[] = [
            'id' => 'stock_1',
            'name' => 'Paracetamol 500mg',
            'type' => 'stock',
            'category' => 'Medications',
            'total_quantity' => 150,
            'unit' => 'tablets',
            'source' => 'Stock',
        ];

        $combinedResults[] = [
            'id' => 'pharmacy_1',
            'name' => 'Amoxicillin 250mg',
            'type' => 'pharmacy',
            'category' => 'Antibiotics',
            'dci' => 'Amoxicillin',
            'dosage' => '250mg',
            'forme' => 'Capsule',
            'total_quantity' => 75,
            'unit' => 'capsules',
            'source' => 'Pharmacy',
        ];

        echo "Sample Combined Results:\n";
        foreach ($combinedResults as $product) {
            echo "  - {$product['name']} ({$product['type']}) - {$product['total_quantity']} {$product['unit']}\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n\n";
    }

    echo "=== Testing Complete ===\n";
}

// Run the test
try {
    // Initialize Laravel app context
    $app = require_once __DIR__.'/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    testNursingProducts();
} catch (Exception $e) {
    echo 'Setup Error: '.$e->getMessage()."\n";
}
