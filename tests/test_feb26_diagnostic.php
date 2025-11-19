<?php

/**
 * Detailed Diagnostic for Feb 26, 2026
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\ExcludedDate;
use App\Models\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

$doctorId = 8; // Dr Hiba
$testDate = '2026-02-26';

echo "═══════════════════════════════════════════════════════\n";
echo "  DETAILED DIAGNOSTIC FOR {$testDate}\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Clear cache
Cache::flush();

// Get limited exclusions
$exclusions = ExcludedDate::where('doctor_id', $doctorId)
    ->where('exclusionType', 'limited')
    ->where('start_date', '<=', $testDate)
    ->where(function ($q) use ($testDate) {
        $q->whereNull('end_date')
            ->orWhere('end_date', '>=', $testDate);
    })
    ->where('is_active', true)
    ->get();

echo "Limited Exclusions:\n";
foreach ($exclusions as $exc) {
    echo "  ID: {$exc->id}\n";
    echo "  Shift: {$exc->shift_period}\n";
    echo "  Time: {$exc->start_time} - {$exc->end_time}\n";
    echo "  Patients: {$exc->number_of_patients_per_day}\n";
    echo "  Date Range: {$exc->start_date} to ".($exc->end_date ?? 'same day')."\n";
    echo "  ---\n";
}

// Check regular schedule for Wednesday
$dateObj = Carbon::parse($testDate);
$dayName = $dateObj->format('l'); // "Wednesday"
echo "\nDate: {$testDate} ({$dayName})\n\n";

$schedules = Schedule::where('doctor_id', $doctorId)
    ->where('is_active', true)
    ->where('day_of_week', strtolower($dayName))
    ->get();

echo "Regular Schedule for {$dayName}:\n";
if ($schedules->count() > 0) {
    foreach ($schedules as $sch) {
        echo "  Shift: {$sch->shift_period}\n";
        echo "  Time: {$sch->start_time} - {$sch->end_time}\n";
        echo "  Patients: {$sch->number_of_patients_per_day}\n";
        echo "  ---\n";
    }
} else {
    echo "  No regular schedule for {$dayName}\n";
}

// Check appointments
$appointments = Appointment::where('doctor_id', $doctorId)
    ->whereDate('appointment_date', $testDate)
    ->get();

echo "\nExisting Appointments on {$testDate}:\n";
if ($appointments->count() > 0) {
    echo "  Found {$appointments->count()} appointment(s):\n";
    foreach ($appointments as $apt) {
        echo "    Time: {$apt->appointment_time}\n";
        echo "    Status: {$apt->status}\n";
        echo "    ---\n";
    }
} else {
    echo "  No appointments booked\n";
}

// Now test the actual availability logic
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Testing isDateTrulyAvailable for {$testDate}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Manually call the controller method
$controller = new App\Http\Controllers\AppointmentController;

// Initialize availability data (this is private, so we'll use reflection)
$reflection = new ReflectionClass($controller);
$initMethod = $reflection->getMethod('initAvailabilityData');
$initMethod->setAccessible(true);
$initMethod->invoke($controller, $doctorId);

// Get the availability data
$availDataProp = $reflection->getProperty('availabilityData');
$availDataProp->setAccessible(true);
$availData = $availDataProp->getValue($controller);

echo "Availability Data Loaded:\n";
echo '  Available Months Count: '.count($availData->availableMonths)."\n";
echo '  Limited Excluded Schedules Count: '.$availData->limitedExcludedSchedules->count()."\n";

// Check if Feb 26 is in limited excluded schedules
$feb26Key = '2026-02-26';
if (isset($availData->limitedExcludedSchedules[$feb26Key])) {
    echo "  ✓ Feb 26 found in limitedExcludedSchedules\n";
    echo '  Shifts: '.$availData->limitedExcludedSchedules[$feb26Key]->count()."\n";
} else {
    echo "  ✗ Feb 26 NOT found in limitedExcludedSchedules\n";
}

// Test isDateTrulyAvailable
$isAvailMethod = $reflection->getMethod('isDateTrulyAvailable');
$isAvailMethod->setAccessible(true);

$testDateCarbon = Carbon::parse($testDate);
$isAvailable = $isAvailMethod->invoke($controller, $testDateCarbon);

echo "\nisDateTrulyAvailable({$testDate}): ".($isAvailable ? '✓ TRUE' : '✗ FALSE')."\n";

if (! $isAvailable) {
    echo "\nWhy is it not available? Let's check step by step:\n\n";

    // Check 1: Is in the past?
    $isPast = $testDateCarbon->startOfDay()->lt(Carbon::now()->startOfDay());
    echo '  1. Is in the past? '.($isPast ? 'YES (blocked)' : 'NO')."\n";

    // Check 2: Is completely excluded?
    $isCompletelyExcluded = false;
    foreach ($availData->completeExcludedRanges as $range) {
        if ($testDateCarbon->between($range['start'], $range['end'])) {
            $isCompletelyExcluded = true;
            break;
        }
    }
    echo '  2. Is completely excluded? '.($isCompletelyExcluded ? 'YES (blocked)' : 'NO')."\n";

    // Check 3: Has limited exclusion?
    $hasLimited = isset($availData->limitedExcludedSchedules[$feb26Key])
        && $availData->limitedExcludedSchedules[$feb26Key]->isNotEmpty();
    echo '  3. Has limited exclusion? '.($hasLimited ? 'YES' : 'NO')."\n";

    // Check 4: Is month available?
    $isMonthAvail = in_array($testDateCarbon->format('Y-m'), $availData->availableMonths);
    echo '  4. Is month available? '.($isMonthAvail ? 'YES' : 'NO')."\n";

    // Check 5: Get working hours
    $getWorkingMethod = $reflection->getMethod('getDoctorWorkingHours');
    $getWorkingMethod->setAccessible(true);
    $workingHours = $getWorkingMethod->invoke($controller, $doctorId, $testDate);

    echo '  5. Working hours count: '.count($workingHours)."\n";
    if (count($workingHours) > 0) {
        echo '     First few: '.implode(', ', array_slice($workingHours, 0, 5))."\n";
    }

    // Check 6: Get booked slots
    $getBookedMethod = $reflection->getMethod('getBookedSlots');
    $getBookedMethod->setAccessible(true);
    $bookedSlots = $getBookedMethod->invoke($controller, $doctorId, $testDate);

    echo '  6. Booked slots count: '.count($bookedSlots)."\n";
    if (count($bookedSlots) > 0) {
        echo '     Booked: '.implode(', ', $bookedSlots)."\n";
    }

    // Check 7: Available slots
    $availableSlots = array_diff($workingHours, $bookedSlots);
    echo '  7. Available slots count: '.count($availableSlots)."\n";

    if (count($availableSlots) === 0 && count($workingHours) > 0) {
        echo "     ⚠ All slots are booked!\n";
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
