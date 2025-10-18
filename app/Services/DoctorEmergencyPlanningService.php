<?php

namespace App\Services;

use App\Models\DoctorEmergencyPlanning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DoctorEmergencyPlanningService
{
    public function createPlanning(array $data): DoctorEmergencyPlanning
    {
        // Check for conflicts before creating
        $conflicts = $this->checkScheduleConflicts(
            $data['doctor_id'],
            $data['planning_date'],
            $data['shift_start_time'],
            $data['shift_end_time']
        );

        if ($conflicts->isNotEmpty()) {
            throw new \Exception('Schedule conflict detected. Another doctor is already scheduled during this time.');
        }

        return DoctorEmergencyPlanning::create($data);
    }

    public function updatePlanning(DoctorEmergencyPlanning $planning, array $data): DoctorEmergencyPlanning
    {
        // Check for conflicts excluding current planning
        $conflicts = $this->checkScheduleConflicts(
            $data['doctor_id'],
            $data['planning_date'],
            $data['shift_start_time'],
            $data['shift_end_time'],
            $planning->id
        );

        if ($conflicts->isNotEmpty()) {
            throw new \Exception('Schedule conflict detected. Another doctor is already scheduled during this time.');
        }

        $planning->update($data);

        return $planning;
    }

    public function checkScheduleConflicts(
        int $doctorId,
        string $planningDate,
        string $shiftStartTime,
        string $shiftEndTime,
        ?int $excludeId = null
    ): Collection {
        // Convert time strings to comparable format (HH:MM:SS)
        $startTime = strlen($shiftStartTime) == 5 ? $shiftStartTime.':00' : $shiftStartTime;
        $endTime = strlen($shiftEndTime) == 5 ? $shiftEndTime.':00' : $shiftEndTime;

        $newShiftStart = Carbon::createFromFormat('H:i:s', $startTime);
        $newShiftEnd = Carbon::createFromFormat('H:i:s', $endTime);
        $isNewShiftOvernight = $newShiftEnd->lessThanOrEqualTo($newShiftStart);

        $conflictingPlannings = collect();

        // Get all potential conflicting plannings in a broader time range
        $prevDay = Carbon::parse($planningDate)->subDay()->toDateString();
        $nextDay = Carbon::parse($planningDate)->addDay()->toDateString();

        $allPlannings = DoctorEmergencyPlanning::where('is_active', true)
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->whereIn('planning_date', [$prevDay, $planningDate, $nextDay])
            ->with(['doctor.user', 'service'])
            ->get();

        foreach ($allPlannings as $existing) {
            $existingDate = $existing->planning_date;
            $existingStart = Carbon::createFromFormat('H:i:s', strlen($existing->shift_start_time) == 5 ? $existing->shift_start_time.':00' : $existing->shift_start_time);
            $existingEnd = Carbon::createFromFormat('H:i:s', strlen($existing->shift_end_time) == 5 ? $existing->shift_end_time.':00' : $existing->shift_end_time);
            $isExistingOvernight = $existingEnd->lessThanOrEqualTo($existingStart);

            // Create actual date-time objects for precise comparison
            $newStart = Carbon::parse($planningDate)->setTimeFromTimeString($startTime);
            $newEnd = $isNewShiftOvernight
                ? Carbon::parse($planningDate)->addDay()->setTimeFromTimeString($endTime)
                : Carbon::parse($planningDate)->setTimeFromTimeString($endTime);

            $existStart = Carbon::parse($existingDate)->setTimeFromTimeString($existing->shift_start_time);
            $existEnd = $isExistingOvernight
                ? Carbon::parse($existingDate)->addDay()->setTimeFromTimeString($existing->shift_end_time)
                : Carbon::parse($existingDate)->setTimeFromTimeString($existing->shift_end_time);

            // Check for overlap: (start1 < end2) AND (end1 > start2)
            if ($newStart->lessThan($existEnd) && $newEnd->greaterThan($existStart)) {
                $conflictingPlannings->push($existing);
            }
        }

        return $conflictingPlannings;
    }

    public function getMonthlyOverview(int $month, int $year): array
    {
        $plannings = DoctorEmergencyPlanning::with(['doctor.user', 'service'])
            ->forMonthYear($month, $year)
            ->active()
            ->orderBy('planning_date')
            ->orderBy('shift_start_time')
            ->get();

        // Group by date for calendar view
        $groupedByDate = $plannings->groupBy('planning_date');

        // Statistics
        $stats = [
            'total_shifts' => $plannings->count(),
            'day_shifts' => $plannings->where('shift_type', 'day')->count(),
            'night_shifts' => $plannings->where('shift_type', 'night')->count(),
            'emergency_shifts' => $plannings->where('shift_type', 'emergency')->count(),
            'doctors_count' => $plannings->pluck('doctor_id')->unique()->count(),
            'services_count' => $plannings->pluck('service_id')->unique()->count(),
        ];

        // Coverage analysis - check if all days of the month have coverage
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $coveredDays = $groupedByDate->keys()->map(function ($date) {
            return Carbon::parse($date)->day;
        })->unique()->count();

        $coverage = [
            'total_days' => $daysInMonth,
            'covered_days' => $coveredDays,
            'coverage_percentage' => round(($coveredDays / $daysInMonth) * 100, 2),
        ];

        return [
            'plannings' => $groupedByDate,
            'statistics' => $stats,
            'coverage' => $coverage,
        ];
    }

    public function generateMonthlyReport(int $month, int $year): array
    {
        $overview = $this->getMonthlyOverview($month, $year);

        // Doctor workload analysis
        $doctorWorkload = DoctorEmergencyPlanning::with(['doctor.user'])
            ->forMonthYear($month, $year)
            ->active()
            ->get()
            ->groupBy('doctor_id')
            ->map(function ($plannings, $doctorId) {
                $doctor = $plannings->first()->doctor;

                return [
                    'doctor' => $doctor,
                    'total_shifts' => $plannings->count(),
                    'day_shifts' => $plannings->where('shift_type', 'day')->count(),
                    'night_shifts' => $plannings->where('shift_type', 'night')->count(),
                    'emergency_shifts' => $plannings->where('shift_type', 'emergency')->count(),
                    'total_hours' => $this->calculateTotalHours($plannings),
                ];
            })
            ->values()
            ->sortByDesc('total_shifts');

        return array_merge($overview, [
            'doctor_workload' => $doctorWorkload,
        ]);
    }

    private function calculateTotalHours(Collection $plannings): float
    {
        return $plannings->sum(function ($planning) {
            $start = Carbon::createFromFormat('H:i:s', $planning->shift_start_time);
            $end = Carbon::createFromFormat('H:i:s', $planning->shift_end_time);

            // Handle overnight shifts
            if ($end->lessThan($start)) {
                $end->addDay();
            }

            return $start->diffInHours($end);
        });
    }

    public function getAvailableDoctors(string $date, string $startTime, string $endTime): Collection
    {
        // Convert time strings to comparable format (HH:MM:SS)
        $startTime = strlen($startTime) == 5 ? $startTime.':00' : $startTime;
        $endTime = strlen($endTime) == 5 ? $endTime.':00' : $endTime;

        $busyDoctorIds = DoctorEmergencyPlanning::where('planning_date', $date)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('shift_start_time', '<', $endTime)
                    ->where('shift_end_time', '>', $startTime);
            })
            ->pluck('doctor_id');

        return User::where('role', 'doctor')
            ->orWhere('fonction', 'like', '%doctor%')
            ->orWhere('fonction', 'like', '%médecin%')
            ->whereNotIn('id', $busyDoctorIds)
            ->select('id', 'nom', 'prenom', 'email', 'fonction')
            ->orderBy('nom')
            ->get();
    }

    public function getNextAvailableStartTime(string $date, ?int $doctorId = null): ?string
    {
        // Get all shifts for the date from ALL doctors (ignore doctorId for multi-doctor conflict prevention)
        $shifts = DoctorEmergencyPlanning::where('planning_date', $date)
            ->where('is_active', true)
            ->orderBy('shift_end_time', 'desc')
            ->get();

        if ($shifts->isEmpty()) {
            return '08:00'; // Default start time if no shifts exist
        }

        // Find the latest ending shift from any doctor
        $latestShift = $shifts->first();
        $latestEndTime = Carbon::createFromFormat('H:i:s', $latestShift->shift_end_time);

        // If the latest shift ends before 23:00, suggest that time for next shift
        if ($latestEndTime->hour < 23) {
            return $latestEndTime->format('H:i');
        }

        // If we have near 24-hour coverage, suggest next day 08:00
        return '08:00';
    }

    public function getSmartNextAvailableTime(string $date): array
    {
        // Get all shifts for the specific date from ALL doctors
        $existingShifts = DoctorEmergencyPlanning::where('planning_date', $date)
            ->where('is_active', true)
            ->orderBy('shift_start_time')
            ->get();

        // Also check for overnight shifts from previous day that end on this date
        $prevDay = Carbon::parse($date)->subDay()->toDateString();
        $overnightFromPrevDay = DoctorEmergencyPlanning::where('planning_date', $prevDay)
            ->where('is_active', true)
            ->whereRaw('shift_end_time <= shift_start_time') // overnight shifts
            ->get();

        if ($existingShifts->isEmpty() && $overnightFromPrevDay->isEmpty()) {
            return [
                'suggested_start_time' => '08:00',
                'suggested_end_time' => '17:00',
                'next_day' => false,
                'is_overnight' => false,
            ];
        }

        // Create timeline of all occupied time periods
        $occupiedPeriods = [];

        // Add regular shifts for current day
        foreach ($existingShifts as $shift) {
            $start = Carbon::parse($shift->shift_start_time);
            $end = Carbon::parse($shift->shift_end_time);

            if ($end->lessThanOrEqualTo($start)) {
                // This is an overnight shift starting today
                $occupiedPeriods[] = [
                    'start' => $start,
                    'end' => Carbon::parse('23:59:59'), // End of current day
                    'type' => 'overnight_start',
                ];
                // The overnight part will be handled by next day logic
            } else {
                // Regular shift
                $occupiedPeriods[] = [
                    'start' => $start,
                    'end' => $end,
                    'type' => 'regular',
                ];
            }
        }

        // Add overnight shifts from previous day that end today
        foreach ($overnightFromPrevDay as $shift) {
            $endTime = Carbon::parse($shift->shift_end_time);
            $occupiedPeriods[] = [
                'start' => Carbon::parse('00:00:00'),
                'end' => $endTime,
                'type' => 'overnight_end',
            ];
        }

        // Sort occupied periods by start time
        usort($occupiedPeriods, function ($a, $b) {
            return $a['start']->compare($b['start']);
        });

        // Find the first available gap or the end of all shifts
        $dayStart = Carbon::parse('00:00:00');
        $dayEnd = Carbon::parse('23:59:59');
        $minShiftDuration = 8; // Minimum 8-hour shifts

        // Check for gaps during the day
        $currentTime = $dayStart->copy();

        foreach ($occupiedPeriods as $period) {
            $gapDuration = $currentTime->diffInHours($period['start'], false);

            if ($gapDuration >= $minShiftDuration) {
                // Found a gap that can fit a shift
                $suggestedEnd = $currentTime->copy()->addHours($minShiftDuration);

                return [
                    'suggested_start_time' => $currentTime->format('H:i'),
                    'suggested_end_time' => $suggestedEnd->format('H:i'),
                    'next_day' => false,
                    'is_overnight' => false,
                ];
            }

            // Move to end of current occupied period
            $currentTime = $period['end']->copy()->addMinute();
        }

        // No gap found during day, check if we can add at the end
        $lastOccupiedEnd = null;
        foreach ($occupiedPeriods as $period) {
            if (! $lastOccupiedEnd || $period['end']->greaterThan($lastOccupiedEnd)) {
                $lastOccupiedEnd = $period['end'];
            }
        }

        if ($lastOccupiedEnd && $lastOccupiedEnd->hour < 23) {
            // Can add shift starting after last shift ends
            $remainingHours = 24 - $lastOccupiedEnd->hour - ($lastOccupiedEnd->minute / 60);

            if ($remainingHours >= 2) { // At least 2 hours available
                // Create overnight shift to next morning
                $suggestedStart = $lastOccupiedEnd->copy()->addMinute();

                return [
                    'suggested_start_time' => $suggestedStart->format('H:i'),
                    'suggested_end_time' => '08:00',
                    'next_day' => false,
                    'is_overnight' => true,
                    'suggested_date' => $date, // Stay on same day but shift continues to next day
                ];
            }
        }

        // Full day coverage, suggest next day
        $nextDay = Carbon::parse($date)->addDay()->toDateString();

        return [
            'suggested_start_time' => '08:00',
            'suggested_end_time' => '17:00',
            'next_day' => true,
            'is_overnight' => false,
            'suggested_date' => $nextDay,
        ];
    }

    public function getDayScheduleOverview(string $date): array
    {
        $shifts = DoctorEmergencyPlanning::with(['doctor', 'service'])
            ->where('planning_date', $date)
            ->where('is_active', true)
            ->orderBy('shift_start_time')
            ->get();

        $timeline = [];
        foreach ($shifts as $shift) {
            $timeline[] = [
                'start_time' => $shift->shift_start_time,
                'end_time' => $shift->shift_end_time,
                'doctor_name' => $shift->doctor->nom.' '.$shift->doctor->prenom,
                'service_name' => $shift->service->nom,
                'shift_type' => $shift->shift_type,
                'id' => $shift->id,
            ];
        }

        return [
            'shifts' => $timeline,
            'total_shifts' => count($timeline),
            'coverage_hours' => $this->calculateCoverageHours($shifts),
        ];
    }

    private function calculateCoverageHours(Collection $shifts): float
    {
        if ($shifts->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        foreach ($shifts as $shift) {
            $start = Carbon::createFromFormat('H:i:s', $shift->shift_start_time);
            $end = Carbon::createFromFormat('H:i:s', $shift->shift_end_time);

            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $totalHours += $start->diffInHours($end, false);
        }

        return $totalHours;
    }
}
