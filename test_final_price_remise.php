<?php

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Reception\ficheNavette;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Patient;
use App\Models\User;

echo "=== Testing Final Price Remise Storage ===\n\n";

try {
    // Find a test fiche navette with items
    $ficheNavette = ficheNavette::with(['items', 'items.dependencies'])->first();
    
    if (!$ficheNavette) {
        echo "No fiche navette found for testing.\n";
        exit(1);
    }
    
    echo "Testing with fiche navette ID: {$ficheNavette->id}\n";
    echo "Patient: {$ficheNavette->patient->first_name} {$ficheNavette->patient->last_name}\n\n";
    
    // Check current items and their final_price values
    echo "=== Current Items and Prices ===\n";
    foreach ($ficheNavette->items as $item) {
        echo "Item ID: {$item->id}\n";
        echo "  Prestation: " . ($item->prestation ? $item->prestation->name : 'N/A') . "\n";
        echo "  Base Price: {$item->base_price}\n";
        echo "  Final Price: {$item->final_price}\n";
        echo "  Patient Share: {$item->patient_share}\n";
        
        // Check dependencies
        if ($item->dependencies && $item->dependencies->count() > 0) {
            echo "  Dependencies:\n";
            foreach ($item->dependencies as $dep) {
                echo "    Dep ID: {$dep->id}\n";
                echo "    Base Price: {$dep->base_price}\n";
                echo "    Final Price: {$dep->final_price}\n";
                echo "    Patient Share: {$dep->patient_share}\n";
            }
        }
        echo "\n";
    }
    
    // Test case: Apply a 10% discount to the first item
    $firstItem = $ficheNavette->items->first();
    if ($firstItem) {
        echo "=== Testing Remise Application ===\n";
        echo "Applying 10% discount to item ID: {$firstItem->id}\n";
        
        $originalFinalPrice = (float) $firstItem->final_price;
        $discountedPrice = $originalFinalPrice * 0.9; // 10% discount
        
        echo "Original final price: {$originalFinalPrice}\n";
        echo "Discounted price (10% off): {$discountedPrice}\n";
        
        // Simulate the remise application (like in RemiseController)
        $firstItem->final_price = $discountedPrice;
        $firstItem->save();
        
        // Reload to verify
        $firstItem->refresh();
        echo "Final price after remise: {$firstItem->final_price}\n";
        
        // Check if it was saved correctly
        if ((float) $firstItem->final_price == $discountedPrice) {
            echo "✅ Remise applied correctly to ficheNavetteItem\n";
        } else {
            echo "❌ Remise NOT applied correctly to ficheNavetteItem\n";
            echo "Expected: {$discountedPrice}, Got: {$firstItem->final_price}\n";
        }
        
        // Test dependencies if they exist
        $firstDep = $firstItem->dependencies->first();
        if ($firstDep) {
            echo "\nTesting dependency remise...\n";
            $originalDepPrice = (float) $firstDep->final_price;
            $discountedDepPrice = $originalDepPrice * 0.9; // 10% discount
            
            echo "Original dependency final price: {$originalDepPrice}\n";
            echo "Discounted dependency price (10% off): {$discountedDepPrice}\n";
            
            $firstDep->final_price = $discountedDepPrice;
            $firstDep->save();
            
            // Reload to verify
            $firstDep->refresh();
            echo "Final price after remise: {$firstDep->final_price}\n";
            
            if ((float) $firstDep->final_price == $discountedDepPrice) {
                echo "✅ Remise applied correctly to ItemDependency\n";
            } else {
                echo "❌ Remise NOT applied correctly to ItemDependency\n";
                echo "Expected: {$discountedDepPrice}, Got: {$firstDep->final_price}\n";
            }
        }
        
        // Restore original prices for cleanup
        $firstItem->final_price = $originalFinalPrice;
        $firstItem->save();
        if ($firstDep) {
            $firstDep->final_price = $originalDepPrice;
            $firstDep->save();
        }
        echo "\n✅ Test completed and original prices restored\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
