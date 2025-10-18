<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExcludedDate;
use Illuminate\Support\Carbon;

$doctorId = 8;
$now = Carbon::now();

echo "Current Time: {$now}\n";
echo "Current Time Start of Day: {$now->copy()->startOfDay()}\n\n";

$excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
    $query->where('doctor_id', $doctorId)
        ->orWhereNull('doctor_id');
})->get();

echo "All Excluded Dates for Doctor {$doctorId}:\n";
echo str_repeat("=", 70) . "\n";

foreach ($excludedDates as $ed) {
    $endDate = Carbon::parse($ed->end_date ?? $ed->start_date);
    $isLimited = $ed->exclusionType === 'limited';
    $isActive = $ed->is_active === true;
    $isFuture = $endDate->gte($now->copy()->startOfDay());
    
    $passes = $isLimited && $isActive && $isFuture;
    
    echo "ID: {$ed->id}\n";
    echo "  Type: {$ed->exclusionType}\n";
    echo "  Shift: {$ed->shift_period}\n";
    echo "  Start: {$ed->start_date}\n";
    echo "  End: " . ($ed->end_date ?? 'same day') . "\n";
    echo "  Active: " . ($ed->is_active ? 'Yes' : 'No') . "\n";
    echo "  Is Limited: " . ($isLimited ? 'Yes' : 'No') . "\n";
    echo "  Is Active: " . ($isActive ? 'Yes' : 'No') . "\n";
    echo "  Is Future/Current: " . ($isFuture ? 'Yes' : 'No') . " (end date {$endDate} >= {$now->copy()->startOfDay()})\n";
    echo "  PASSES FILTER: " . ($passes ? '✓ YES' : '✗ NO') . "\n";
    echo str_repeat("-", 70) . "\n";
}

echo "\nFiltering Logic Test:\n";
echo "Filter: exclusionType === 'limited' && is_active === true && end_date >= now\n\n";

$filtered = $excludedDates->filter(fn ($ed) => 
    $ed->exclusionType === 'limited' && 
    $ed->is_active === true && 
    Carbon::parse($ed->end_date ?? $ed->start_date)->gte($now->copy()->startOfDay())
);

echo "Filtered Count: {$filtered->count()}\n";
foreach ($filtered as $ed) {
    echo "  - ID {$ed->id}: {$ed->start_date} (shift: {$ed->shift_period})\n";
}
