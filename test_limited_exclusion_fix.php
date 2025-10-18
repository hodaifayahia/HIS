<?php

/**
 * Test Script for Limited Exclusion Calendar Fix
 * 
 * This tests that limited exclusions are found even in:
 * - Closed months
 * - Non-working days
 * - Dates without regular schedules
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\AppointmentAvailableMonth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  LIMITED EXCLUSION CALENDAR FIX - TEST SCRIPT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get a doctor to test with
$doctor = Doctor::with('user')->first();

if (!$doctor) {
    echo "❌ ERROR: No doctors found in database!\n";
    echo "Please create a doctor first.\n";
    exit(1);
}

echo "✓ Testing with Doctor: " . ($doctor->user->name ?? "ID {$doctor->id}") . "\n";
echo "  Doctor ID: {$doctor->id}\n\n";

// Clear cache to ensure fresh data
Cache::flush();
echo "✓ Cache cleared\n\n";

// Test Date: February 26, 2026 (as mentioned by user)
$testDate = '2026-02-26';
$testDateObj = Carbon::parse($testDate);

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Check Current State\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check if February 2026 is in available months
$feb2026Available = AppointmentAvailableMonth::where('doctor_id', $doctor->id)
    ->where('year', 2026)
    ->where('month', 2)
    ->where('is_available', true)
    ->exists();

echo "Month Status:\n";
echo "  February 2026 available: " . ($feb2026Available ? "✓ YES" : "✗ NO (closed)") . "\n\n";

// Check for existing limited exclusions on test date
$existingExclusions = ExcludedDate::where('doctor_id', $doctor->id)
    ->where('exclusionType', 'limited')
    ->where('start_date', '<=', $testDate)
    ->where(function($q) use ($testDate) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', $testDate);
    })
    ->get();

echo "Limited Exclusions for {$testDate}:\n";
if ($existingExclusions->count() > 0) {
    echo "  ✓ Found {$existingExclusions->count()} exclusion(s):\n";
    foreach ($existingExclusions as $exc) {
        echo "    - Shift: {$exc->shift_period}\n";
        echo "      Time: {$exc->start_time} - {$exc->end_time}\n";
        echo "      Patients: {$exc->number_of_patients_per_day}\n";
        echo "      Active: " . ($exc->is_active ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "  ✗ No limited exclusions found for this date\n";
    echo "  ℹ  You need to create one first!\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Simulate checkAvailability API Call\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Simulate the API request
    $request = new Illuminate\Http\Request([
        'doctor_id' => $doctor->id,
        'date' => $testDate,
    ]);

    $controller = new App\Http\Controllers\AppointmentController();
    $response = $controller->checkAvailability($request);
    
    $responseData = json_decode($response->getContent(), true);
    
    echo "API Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    
    // Analyze the response
    if ($responseData['is_available']) {
        $returnedDate = $responseData['next_available_date'];
        
        if ($returnedDate === $testDate) {
            echo "✓ SUCCESS: API correctly returned {$testDate}\n";
            echo "✓ Limited exclusion was found and used!\n";
            
            if (isset($responseData['available_slots']) && count($responseData['available_slots']) > 0) {
                echo "✓ Available slots returned: " . count($responseData['available_slots']) . " slots\n";
                echo "  First few slots: " . implode(', ', array_slice($responseData['available_slots'], 0, 5)) . "...\n";
            } else {
                echo "⚠ WARNING: No available slots in response\n";
            }
        } else {
            echo "✗ FAIL: API returned {$returnedDate} instead of {$testDate}\n";
            echo "✗ Limited exclusion was NOT found (month was skipped)\n";
            
            if (!$feb2026Available && $existingExclusions->count() > 0) {
                echo "\n❌ BUG CONFIRMED:\n";
                echo "   - February 2026 is closed\n";
                echo "   - Limited exclusion exists for {$testDate}\n";
                echo "   - But API skipped to {$returnedDate}\n";
                echo "   - This indicates the fix didn't work!\n";
            }
        }
    } else {
        echo "✗ FAIL: API says no dates available\n";
        
        if ($existingExclusions->count() > 0) {
            echo "✗ But limited exclusion exists! Something is wrong.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR during API test:\n";
    echo "   {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Test Month Skip Logic\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Test searching from Feb 1 when Feb is closed but has limited exclusion on Feb 26
$searchFromDate = '2026-02-01';

try {
    $request = new Illuminate\Http\Request([
        'doctor_id' => $doctor->id,
        'date' => $searchFromDate,
    ]);

    $controller = new App\Http\Controllers\AppointmentController();
    $response = $controller->checkAvailability($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "Searching from: {$searchFromDate}\n";
    echo "Expected to find: {$testDate} (limited exclusion)\n";
    echo "Actually found: " . ($responseData['next_available_date'] ?? 'none') . "\n\n";
    
    if (isset($responseData['next_available_date'])) {
        $foundDate = Carbon::parse($responseData['next_available_date']);
        $expectedDate = Carbon::parse($testDate);
        
        if ($foundDate->eq($expectedDate)) {
            echo "✓ SUCCESS: Correctly found limited exclusion at {$testDate}\n";
            echo "✓ Did NOT skip February month\n";
        } elseif ($foundDate->gt($expectedDate)) {
            echo "✗ FAIL: Found {$foundDate->format('Y-m-d')} which is AFTER {$testDate}\n";
            echo "✗ This means the search skipped over the limited exclusion\n";
        } else {
            echo "ℹ Found earlier date: {$foundDate->format('Y-m-d')}\n";
            echo "ℹ This might be another available date before the limited exclusion\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($existingExclusions->count() === 0) {
    echo "⚠ SETUP REQUIRED:\n";
    echo "  To properly test, you need to:\n";
    echo "  1. Create a limited exclusion for {$testDate}\n";
    echo "  2. Set morning/afternoon times (e.g., 09:00-17:00)\n";
    echo "  3. Set patient count (e.g., 15 patients)\n";
    echo "  4. Run this test again\n\n";
    echo "  Example API call to create:\n";
    echo "  POST /api/excluded-dates\n";
    echo "  {\n";
    echo "    \"doctor_id\": {$doctor->id},\n";
    echo "    \"start_date\": \"{$testDate}\",\n";
    echo "    \"exclusionType\": \"limited\",\n";
    echo "    \"is_morning_active\": true,\n";
    echo "    \"morning_start_time\": \"09:00\",\n";
    echo "    \"morning_end_time\": \"12:00\",\n";
    echo "    \"morning_patients\": 10,\n";
    echo "    \"is_afternoon_active\": true,\n";
    echo "    \"afternoon_start_time\": \"14:00\",\n";
    echo "    \"afternoon_end_time\": \"17:00\",\n";
    echo "    \"afternoon_patients\": 10\n";
    echo "  }\n";
} else {
    echo "✓ Limited exclusion exists for testing\n";
    
    if (!$feb2026Available) {
        echo "✓ February 2026 is closed (good for testing)\n";
    } else {
        echo "ℹ February 2026 is open (test still valid but less challenging)\n";
    }
    
    echo "\nRe-run this test after making changes to verify the fix works!\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  TEST COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
