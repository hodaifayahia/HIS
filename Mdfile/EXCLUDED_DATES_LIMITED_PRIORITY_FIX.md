# Excluded Dates Limited Patient Priority Fix

## Problem
When creating a "Limited Patients" exclusion for a doctor, the system was not properly:
1. Using the exclusion's custom time slots and patient numbers
2. Making the doctor available on days they normally don't work
3. Properly overriding regular schedules with the exclusion settings

## Solution

### 1. **Date Range Expansion** (Lines 103-115)
The limited exclusions are now expanded to cover ALL dates in their range, not just the start date:

```php
// Before: Only the start date had the exclusion
$limitedExcludedSchedules = $excludedDates->filter(...)->keyBy(fn ($ed) => Carbon::parse($ed->start_date)->format('Y-m-d'));

// After: All dates in the range have the exclusion
$excludedDates->each(function ($ed) use (&$limitedExcludedSchedules) {
    $startDate = Carbon::parse($ed->start_date);
    $endDate = Carbon::parse($ed->end_date ?? $ed->start_date);
    
    $currentDate = $startDate->copy();
    while ($currentDate->lte($endDate)) {
        $limitedExcludedSchedules->put($currentDate->format('Y-m-d'), $ed);
        $currentDate->addDay();
    }
});
```

### 2. **Priority System** (Lines 933-1044)
Limited exclusions now have **HIGHEST PRIORITY** and override all other schedules:

**Priority Order:**
1. ✅ **Limited Exclusions** (highest) - Custom working hours for specific dates
2. ✅ **Specific Date Schedules** - Doctor's schedule for a specific date
3. ✅ **Recurring Schedules** (lowest) - Doctor's regular weekly schedule

### 3. **Shift Processing for Limited Exclusions** (Lines 947-967)
Limited exclusions now properly build schedules from their morning and afternoon shifts:

```php
if (isset($data->limitedExcludedSchedules[$dateString])) {
    $limitedExclusion = $data->limitedExcludedSchedules[$dateString];
    $isLimitedExclusion = true;
    
    // Morning shift
    if ($limitedExclusion->is_morning_active) {
        $activeSchedules->push((object) [
            'shift_period' => 'morning',
            'start_time' => $limitedExclusion->morning_start_time,
            'end_time' => $limitedExclusion->morning_end_time,
            'number_of_patients_per_day' => $limitedExclusion->morning_patients ?? 0,
        ]);
    }
    
    // Afternoon shift
    if ($limitedExclusion->is_afternoon_active) {
        $activeSchedules->push((object) [
            'shift_period' => 'afternoon',
            'start_time' => $limitedExclusion->afternoon_start_time,
            'end_time' => $limitedExclusion->afternoon_end_time,
            'number_of_patients_per_day' => $limitedExclusion->afternoon_patients ?? 0,
        ]);
    }
}
```

### 4. **Patient-Based Slot Calculation** (Lines 990-1034)
Limited exclusions ALWAYS use patient-based calculation instead of fixed time slots:

```php
if ($timeSlotMinutes > 0 && !$isLimitedExclusion) {
    // Fixed time slot approach (only for regular schedules)
    // ...
} else {
    // Patient-based calculation (for limited exclusions or when time_slot is not set)
    $patientsForShift = (int) ($schedule->number_of_patients_per_day ?? 0);
    // Calculate slots based on patient count
    // ...
}
```

## How It Works Now

### Example Scenario 1: Doctor Doesn't Normally Work on Sundays
1. Create a Limited Exclusion for Sunday, Dec 15, 2024
2. Set Morning: 09:00-12:00, 10 patients
3. Set Afternoon: 14:00-17:00, 8 patients
4. **Result**: Doctor is now available on that Sunday with 18 total slots (10 + 8)

### Example Scenario 2: Doctor Normally Works Monday 08:00-17:00
1. Doctor's regular Monday schedule: 08:00-17:00, 20 patients
2. Create Limited Exclusion for Monday, Dec 16, 2024
3. Set Morning only: 10:00-12:00, 5 patients
4. **Result**: On Dec 16, doctor only works 10:00-12:00 with 5 slots (exclusion overrides regular schedule)

### Example Scenario 3: Date Range Exclusion
1. Create Limited Exclusion for Dec 20-25, 2024 (6 days)
2. Set custom hours for the holiday period
3. **Result**: All 6 days use the exclusion's settings, regardless of regular schedules

## Benefits

✅ **Priority**: Limited exclusions override regular schedules
✅ **Flexibility**: Doctor can work on normally off days
✅ **Accuracy**: Uses exact time and patient limits from exclusion
✅ **Range Support**: Single exclusion covers multiple dates
✅ **Shift Support**: Morning and afternoon shifts work independently

## Files Modified
- `app/Http/Controllers/AppointmentController.php`
  - `initAvailabilityData()` method (lines 103-115)
  - `getDoctorWorkingHours()` method (lines 933-1044)

## Testing Checklist
- [ ] Create limited exclusion for a day doctor doesn't normally work
- [ ] Create limited exclusion for a day doctor normally works (verify override)
- [ ] Create limited exclusion for a date range (3+ days)
- [ ] Verify morning-only exclusion works
- [ ] Verify afternoon-only exclusion works
- [ ] Verify both morning and afternoon shifts together
- [ ] Check that appointments can be booked in excluded date slots
- [ ] Verify patient count limits are respected
