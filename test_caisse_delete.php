<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Coffre\Caisse;
use Illuminate\Support\Facades\DB;

// Test the delete functionality
echo "=== Caisse Delete Test ===\n\n";

// First, let's see how many caisses we have
$totalBefore = Caisse::count();
echo "Total caisses before test: $totalBefore\n";

if ($totalBefore === 0) {
    echo "No caisses in database. Creating a test caisse...\n";
    $testCaisse = Caisse::create([
        'name' => 'Test Caisse for Deletion',
        'location' => 'Test Location',
        'is_active' => true,
        'service_id' => 1
    ]);
    echo "Created test caisse with ID: {$testCaisse->id}\n\n";
} else {
    // Get the last caisse
    $testCaisse = Caisse::latest('id')->first();
    echo "Using existing caisse ID: {$testCaisse->id}, Name: {$testCaisse->name}\n\n";
}

$caisseId = $testCaisse->id;

// Check if it exists before delete
$existsBefore = Caisse::where('id', $caisseId)->exists();
echo "Caisse exists before delete: " . ($existsBefore ? 'YES' : 'NO') . "\n";

if ($existsBefore) {
    // Now delete it
    echo "\n--- Attempting to delete caisse with ID: $caisseId ---\n";
    
    try {
        $caisse = Caisse::find($caisseId);
        if ($caisse) {
            echo "Found caisse: {$caisse->name}\n";
            
            // Delete using the delete method
            $deleted = $caisse->delete();
            echo "Delete operation result: " . ($deleted ? 'TRUE' : 'FALSE') . "\n";
            
            // Check if it's actually deleted
            $existsAfter = Caisse::where('id', $caisseId)->exists();
            echo "Caisse exists after delete: " . ($existsAfter ? 'YES' : 'NO') . "\n";
            
            if (!$existsAfter) {
                echo "\n✓ SUCCESS: Caisse was successfully deleted from the database.\n";
            } else {
                echo "\n✗ PROBLEM: Caisse still exists in the database after delete operation!\n";
                
                // Let's check if soft deletes are enabled
                echo "\n--- Checking for soft deletes ---\n";
                $uses = class_uses(Caisse::class);
                $hasSoftDeletes = isset($uses['Illuminate\Database\Eloquent\SoftDeletes']);
                echo "Has SoftDeletes trait: " . ($hasSoftDeletes ? 'YES' : 'NO') . "\n";
                
                // Check if there's a deleted_at column
                $table = Caisse::getModel()->getTable();
                $columns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", [$table]);
                $columnNames = array_map(function ($col) { return $col->COLUMN_NAME; }, $columns);
                echo "Has deleted_at column: " . (in_array('deleted_at', $columnNames) ? 'YES' : 'NO') . "\n";
                
                echo "\nAll columns in caisses table: " . implode(', ', $columnNames) . "\n";
            }
        } else {
            echo "ERROR: Could not find caisse with ID: $caisseId\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "Exception: " . get_class($e) . "\n";
    }
}

$totalAfter = Caisse::count();
echo "\nTotal caisses after test: $totalAfter\n";
echo "\nDifference: " . ($totalBefore - $totalAfter) . " caisse(s) deleted.\n";

?>
