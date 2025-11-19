<?php

/**
 * Test script for Package Detection Logic
 *
 * This script simulates the package detection functionality
 * Run: php test_package_detection.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;

echo "=== Package Detection Test ===\n\n";

// Test 1: Check if we have any active packages
echo "Test 1: Finding active packages...\n";
$activePackages = PrestationPackage::where('is_active', true)->with('items')->get();
echo 'Found '.$activePackages->count()." active package(s)\n\n";

foreach ($activePackages as $package) {
    echo "  Package: {$package->name} (ID: {$package->id})\n";
    echo "  Price: {$package->price}\n";
    echo '  Prestations in package: ';
    $prestationIds = $package->items->pluck('prestation_id')->toArray();
    echo implode(', ', $prestationIds)."\n";

    // Get prestation names
    $prestations = Prestation::whereIn('id', $prestationIds)->get(['id', 'name']);
    foreach ($prestations as $p) {
        echo "    - [{$p->id}] {$p->name}\n";
    }
    echo "\n";
}

// Test 2: Simulate package detection
echo "\nTest 2: Simulating package detection...\n";

if ($activePackages->count() > 0) {
    $testPackage = $activePackages->first();
    $testPrestationIds = $testPackage->items->pluck('prestation_id')->toArray();

    echo 'Testing with prestation IDs: '.implode(', ', $testPrestationIds)."\n";

    // Simulate the findMatchingPackage logic
    sort($testPrestationIds);
    $found = false;

    foreach ($activePackages as $package) {
        $packagePrestationIds = $package->items->pluck('prestation_id')->toArray();
        sort($packagePrestationIds);

        if ($testPrestationIds === $packagePrestationIds) {
            echo "✓ MATCH FOUND: Package '{$package->name}' (ID: {$package->id})\n";
            $found = true;
            break;
        }
    }

    if (! $found) {
        echo "✗ No matching package found\n";
    }
} else {
    echo "No active packages to test with.\n";
}

// Test 3: Check prestations with dependencies
echo "\n\nTest 3: Finding prestations with dependencies...\n";
$prestationsWithDeps = Prestation::where('is_active', true)
    ->whereNotNull('required_prestations_info')
    ->get(['id', 'name', 'required_prestations_info', 'patient_instructions']);

echo 'Found '.$prestationsWithDeps->count()." prestation(s) with dependencies\n\n";

foreach ($prestationsWithDeps->take(5) as $p) {
    echo "  Prestation: {$p->name} (ID: {$p->id})\n";
    $deps = $p->required_prestations_info;
    if (is_array($deps) && count($deps) > 0) {
        echo '    Dependencies: '.implode(', ', $deps)."\n";

        // Get dependency names
        $depPrestations = Prestation::whereIn('id', $deps)->get(['id', 'name']);
        foreach ($depPrestations as $dep) {
            echo "      - [{$dep->id}] {$dep->name}\n";
        }
    }
    if ($p->patient_instructions) {
        echo '    Instructions: '.substr($p->patient_instructions, 0, 60)."...\n";
    }
    echo "\n";
}

// Test 4: Sample package detection scenarios
echo "\nTest 4: Testing various prestation combinations...\n";

$testCases = [
    [1, 2, 3],
    [1, 2],
    [5, 6, 7, 8],
];

foreach ($testCases as $index => $testCase) {
    echo '  Test case '.($index + 1).': ['.implode(', ', $testCase)."]\n";

    sort($testCase);
    $matched = false;

    foreach ($activePackages as $package) {
        $packagePrestationIds = $package->items->pluck('prestation_id')->toArray();
        sort($packagePrestationIds);

        if ($testCase === $packagePrestationIds) {
            echo "    ✓ Matches package: {$package->name}\n";
            $matched = true;
            break;
        }
    }

    if (! $matched) {
        echo "    ✗ No package match - will store individual prestations\n";
    }
}

echo "\n=== Test Complete ===\n";
