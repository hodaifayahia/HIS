<?php

namespace App\Services;

use App\Models\NursingEmergencyPlanning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NursingEmergencyPlanningService
{
    public function createPlanning(array $data): NursingEmergencyPlanning
    {
        $conflicts = $this->checkScheduleConflicts(
            $data['nurse_id'],
            $data['planning_date'],
            $data['shift_start_time'],
            $data['shift_end_time']
        );

        if ($conflicts->isNotEmpty()) {
            throw new \Exception('Schedule conflict detected. Another nurse is already scheduled during this time.');
        }

        return NursingEmergencyPlanning::create($data);
    }

    public function updatePlanning(NursingEmergencyPlanning $planning, array $data): NursingEmergencyPlanning
    {
        $conflicts = $this->checkScheduleConflicts(
            $data['nurse_id'],
            $data['planning_date'],
            $data['shift_start_time'],
            $data['shift_end_time'],
            $planning->id
        );

        if ($conflicts->isNotEmpty()) {
            throw new \Exception('Schedule conflict detected. Another nurse is already scheduled during this time.');
        }

        $planning->update($data);

        return $planning;
    }

    public function checkScheduleConflicts(
        int $nurseId,
        string $planningDate,
        string $shiftStartTime,
        string $shiftEndTime,
        ?int $excludeId = null
    ): Collection {
        $startTime = strlen($shiftStartTime) === 5 ? $shiftStartTime.':00' : $shiftStartTime;
        $endTime = strlen($shiftEndTime) === 5 ? $shiftEndTime.':00' : $shiftEndTime;

        $newShiftStart = Carbon::createFromFormat('H:i:s', $startTime);
        $newShiftEnd = Carbon::createFromFormat('H:i:s', $endTime);
        $isNewShiftOvernight = $newShiftEnd->lessThanOrEqualTo($newShiftStart);

        $conflictingPlannings = collect();

        $prevDay = Carbon::parse($planningDate)->subDay()->toDateString();
        $nextDay = Carbon::parse($planningDate)->addDay()->toDateString();

        $allPlannings = NursingEmergencyPlanning::where('is_active', true)
            ->where('nurse_id', $nurseId)
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->whereIn('planning_date', [$prevDay, $planningDate, $nextDay])
            ->with(['nurse', 'service'])
            ->get();

        foreach ($allPlannings as $existing) {
            $existingDate = $existing->planning_date;
            $existingStart = Carbon::createFromFormat(
                'H:i:s',
                strlen($existing->shift_start_time) === 5 ? $existing->shift_start_time.':00' : $existing->shift_start_time
            );
            $existingEnd = Carbon::createFromFormat(
                'H:i:s',
                strlen($existing->shift_end_time) === 5 ? $existing->shift_end_time.':00' : $existing->shift_end_time
            );
            $isExistingOvernight = $existingEnd->lessThanOrEqualTo($existingStart);

            $newStart = Carbon::parse($planningDate)->setTimeFromTimeString($startTime);
            $newEnd = $isNewShiftOvernight
                ? Carbon::parse($planningDate)->addDay()->setTimeFromTimeString($endTime)
                : Carbon::parse($planningDate)->setTimeFromTimeString($endTime);

            $existStart = Carbon::parse($existingDate)->setTimeFromTimeString($existing->shift_start_time);
            $existEnd = $isExistingOvernight
                ? Carbon::parse($existingDate)->addDay()->setTimeFromTimeString($existing->shift_end_time)
                : Carbon::parse($existingDate)->setTimeFromTimeString($existing->shift_end_time);

            if ($newStart->lessThan($existEnd) && $newEnd->greaterThan($existStart)) {
                $conflictingPlannings->push($existing);
            }
        }

        return $conflictingPlannings;
    }

    public function getMonthlyOverview(int $month, int $year): array
    {
        $plannings = NursingEmergencyPlanning::with(['nurse', 'service'])
            ->forMonthYear($month, $year)
            ->active()
            ->orderBy('planning_date')
            ->orderBy('shift_start_time')
            ->get();

        $groupedByDate = $plannings->groupBy('planning_date');

        $stats = [
            'total_shifts' => $plannings->count(),
            'day_shifts' => $plannings->where('shift_type', 'day')->count(),
            'night_shifts' => $plannings->where('shift_type', 'night')->count(),
            'emergency_shifts' => $plannings->where('shift_type', 'emergency')->count(),
            'nurses_count' => $plannings->pluck('nurse_id')->unique()->count(),
            'services_count' => $plannings->pluck('service_id')->unique()->count(),
        ];

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $coveredDays = $groupedByDate->keys()->map(function ($date) {
            return Carbon::parse($date)->day;
        })->unique()->count();

        $coverage = [
            'total_days' => $daysInMonth,
            'covered_days' => $coveredDays,
            'coverage_percentage' => $daysInMonth > 0 ? round(($coveredDays / $daysInMonth) * 100, 2) : 0,
        ];

        return [
            'plannings' => $groupedByDate,
            'statistics' => $stats,
            'coverage' => $coverage,
        ];
    }

    public function getAvailableNurses(string $date, string $startTime, string $endTime): Collection
    {
        $startTime = strlen($startTime) === 5 ? $startTime.':00' : $startTime;
        $endTime = strlen($endTime) === 5 ? $endTime.':00' : $endTime;

        $busyNurseIds = NursingEmergencyPlanning::where('planning_date', $date)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('shift_start_time', '<', $endTime)
                    ->where('shift_end_time', '>', $startTime);
            })
            ->pluck('nurse_id');

        return User::where('role', 'nurse')
            ->whereNotIn('id', $busyNurseIds)
            ->select('id', 'name', 'email', 'fonction')
            ->orderBy('name')
            ->get();
    }

    public function getNextAvailableStartTime(string $date): ?string
    {
        $shifts = NursingEmergencyPlanning::where('planning_date', $date)
            ->where('is_active', true)
            ->orderBy('shift_end_time', 'desc')
            ->get();

        if ($shifts->isEmpty()) {
            return '08:00';
        }

        $latestShift = $shifts->first();
        $latestEndTime = Carbon::createFromFormat('H:i:s', $latestShift->shift_end_time);

        if ($latestEndTime->hour < 23) {
            return $latestEndTime->format('H:i');
        }

        return '08:00';
    }

    public function getSmartNextAvailableTime(string $date): array
    {
        $existingShifts = NursingEmergencyPlanning::where('planning_date', $date)
            ->where('is_active', true)
            ->orderBy('shift_start_time')
            ->get();

        $prevDay = Carbon::parse($date)->subDay()->toDateString();
        $overnightFromPrevDay = NursingEmergencyPlanning::where('planning_date', $prevDay)
            ->where('is_active', true)
            ->whereRaw('shift_end_time <= shift_start_time')
            ->get();

        if ($existingShifts->isEmpty() && $overnightFromPrevDay->isEmpty()) {
            return [
                'suggested_start_time' => '08:00',
                'suggested_end_time' => '17:00',
                'next_day' => false,
                'is_overnight' => false,
            ];
        }

        $occupiedPeriods = [];

        foreach ($existingShifts as $shift) {
            $start = Carbon::parse($shift->shift_start_time);
            $end = Carbon::parse($shift->shift_end_time);

            if ($end->lessThanOrEqualTo($start)) {
                $occupiedPeriods[] = [
                    'start' => $start,
                    'end' => Carbon::parse('23:59:59'),
                    'type' => 'overnight_start',
                ];
            } else {
                $occupiedPeriods[] = [
                    'start' => $start,
                    'end' => $end,
                    'type' => 'regular',
                ];
            }
        }

        foreach ($overnightFromPrevDay as $shift) {
            $endTime = Carbon::parse($shift->shift_end_time);
            $occupiedPeriods[] = [
                'start' => Carbon::parse('00:00:00'),
                'end' => $endTime,
                'type' => 'overnight_end',
            ];
        }

        usort($occupiedPeriods, function ($a, $b) {
            return $a['start']->compare($b['start']);
        });

        $dayStart = Carbon::parse('00:00:00');
        $minShiftDuration = 8;
        $currentTime = $dayStart->copy();

        foreach ($occupiedPeriods as $period) {
            $gapDuration = $currentTime->diffInHours($period['start'], false);

            if ($gapDuration >= $minShiftDuration) {
                $suggestedEnd = $currentTime->copy()->addHours($minShiftDuration);

                return [
                    'suggested_start_time' => $currentTime->format('H:i'),
                    'suggested_end_time' => $suggestedEnd->format('H:i'),
                    'next_day' => false,
                    'is_overnight' => false,
                ];
            }

            $currentTime = $period['end']->copy()->addMinute();
        }

        $lastOccupiedEnd = null;
        foreach ($occupiedPeriods as $period) {
            if (! $lastOccupiedEnd || $period['end']->greaterThan($lastOccupiedEnd)) {
                $lastOccupiedEnd = $period['end'];
            }
        }

        if ($lastOccupiedEnd && $lastOccupiedEnd->hour < 23) {
            $remainingHours = 24 - $lastOccupiedEnd->hour - ($lastOccupiedEnd->minute / 60);

            if ($remainingHours >= 2) {
                $suggestedStart = $lastOccupiedEnd->copy()->addMinute();

                return [
                    'suggested_start_time' => $suggestedStart->format('H:i'),
                    'suggested_end_time' => '08:00',
                    'next_day' => false,
                    'is_overnight' => true,
                    'suggested_date' => $date,
                ];
            }
        }

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
        $shifts = NursingEmergencyPlanning::with(['nurse', 'service'])
            ->where('planning_date', $date)
            ->where('is_active', true)
            ->orderBy('shift_start_time')
            ->get();

        $timeline = [];
        foreach ($shifts as $shift) {
            $timeline[] = [
                'start_time' => $shift->shift_start_time,
                'end_time' => $shift->shift_end_time,
                'nurse_name' => $shift->nurse?->name,
                'service_name' => $shift->service?->name,
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
