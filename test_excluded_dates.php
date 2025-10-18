<?php

/**
 * Test Script for Excluded Dates System
 * Run with: php artisan tinker < test_excluded_dates.php
 */

echo "=== EXCLUDED DATES SYSTEM TEST ===\n\n";

// Test 1: Check if any excluded dates exist
echo "Test 1: Checking excluded dates...\n";
$excludedCount = \App\Models\ExcludedDate::count();
echo "Total excluded dates: {$excludedCount}\n";

if ($excludedCount > 0) {
    $latest = \App\Models\ExcludedDate::latest()->first();
    echo "Latest exclusion:\n";
    echo "  - Doctor ID: " . ($latest->doctor_id ?? 'Global') . "\n";
    echo "  - Date: {$latest->start_date} to " . ($latest->end_date ?? $latest->start_date) . "\n";
    echo "  - Type: {$latest->exclusionType}\n";
    echo "  - Shift: " . ($latest->shift_period ?? 'N/A') . "\n";
    echo "  - Time: " . ($latest->start_time ?? 'N/A') . " - " . ($latest->end_time ?? 'N/A') . "\n";
    echo "  - Patients: " . ($latest->number_of_patients_per_day ?? 'N/A') . "\n";
} else {
    echo "  ⚠️  No excluded dates found in database\n";
}

echo "\n";

// Test 2: Check limited exclusions
echo "Test 2: Checking limited exclusions...\n";
$limitedExclusions = \App\Models\ExcludedDate::where('exclusionType', 'limited')
    ->where('is_active', true)
    ->get();

echo "Active limited exclusions: {$limitedExclusions->count()}\n";

if ($limitedExclusions->count() > 0) {
    // Group by date to see if morning/afternoon pairs exist
    $grouped = $limitedExclusions->groupBy(function($item) {
        return $item->start_date->format('Y-m-d');
    });
    
    foreach ($grouped as $date => $exclusions) {
        echo "\nDate: {$date}\n";
        foreach ($exclusions as $exclusion) {
            echo "  - {$exclusion->shift_period}: {$exclusion->start_time} - {$exclusion->end_time} ({$exclusion->number_of_patients_per_day} patients)\n";
        }
    }
}

echo "\n";

// Test 3: Check complete exclusions
echo "Test 3: Checking complete exclusions...\n";
$completeExclusions = \App\Models\ExcludedDate::where('exclusionType', 'complete')
    ->where('is_active', true)
    ->count();
echo "Active complete exclusions: {$completeExclusions}\n";

echo "\n";

// Test 4: Check if doctors exist
echo "Test 4: Checking doctors...\n";
$doctorCount = \App\Models\Doctor::count();
echo "Total doctors: {$doctorCount}\n";

if ($doctorCount > 0) {
    $doctor = \App\Models\Doctor::first();
    echo "  First doctor ID: {$doctor->id}\n";
    echo "  Time slot: " . ($doctor->time_slot ?? 'Not set') . " minutes\n";
    echo "  Patients based on time: " . ($doctor->patients_based_on_time ? 'Yes' : 'No') . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nTo test appointment booking:\n";
echo "1. Go to appointment booking page\n";
echo "2. Select a doctor with limited exclusions\n";
echo "3. Choose a date with limited exclusions\n";
echo "4. Verify slots match the exclusion times and patient counts\n";
