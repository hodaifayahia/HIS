<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\Doctor;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules for a doctor
     */
    public function index(Request $request)
    {
        $doctorId = $request->doctor_id;

        if (! $doctorId) {
            return response()->json(['error' => 'Doctor ID is required'], 400);
        }

        // Fetch schedules for the specified doctor
        $schedules = Schedule::active()
            ->where('doctor_id', $doctorId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Fetch doctor details
        $doctor = Doctor::where('id', $doctorId)
            ->with('user:id,name')
            ->first();

        if (! $doctor) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }

        return response()->json([
            'doctor_name' => $doctor->user->name,
            'patients_based_on_time' => $doctor->patients_based_on_time,
            'frequency' => $doctor->frequency,
            'schedules' => ScheduleResource::collection($schedules),
        ]);
    }

    /**
     * Store a new schedule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'shift_period' => 'required|in:morning,afternoon',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'number_of_patients_per_day' => 'required|integer|min:1|max:50',
            'break_duration' => 'nullable|integer|min:5|max:120',
            'break_times' => 'nullable|array',
            'break_times.*' => 'date_format:H:i',
            'excluded_dates' => 'nullable|array',
            'excluded_dates.*' => 'date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for conflicts
        if (Schedule::hasConflict(
            $request->doctor_id,
            $request->day_of_week,
            $request->shift_period,
            $request->start_time,
            $request->end_time
        )) {
            return response()->json([
                'error' => 'Schedule conflict detected. This time slot overlaps with an existing schedule.',
            ], 422);
        }

        try {
            $schedule = Schedule::create([
                'doctor_id' => $request->doctor_id,
                'day_of_week' => $request->day_of_week,
                'shift_period' => $request->shift_period,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'number_of_patients_per_day' => $request->number_of_patients_per_day,
                'break_duration' => $request->break_duration,
                'break_times' => $request->break_times,
                'excluded_dates' => $request->excluded_dates,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Schedule created successfully',
                'schedule' => new ScheduleResource($schedule),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create schedule',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific schedule
     */
    public function show($id)
    {
        $schedule = Schedule::with('doctor.user')->find($id);

        if (! $schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        return response()->json([
            'schedule' => new ScheduleResource($schedule),
        ]);
    }

    /**
     * Update a specific schedule
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::find($id);

        if (! $schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'shift_period' => 'sometimes|in:morning,afternoon',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'number_of_patients_per_day' => 'sometimes|integer|min:1|max:50',
            'break_duration' => 'nullable|integer|min:5|max:120',
            'break_times' => 'nullable|array',
            'break_times.*' => 'date_format:H:i',
            'excluded_dates' => 'nullable|array',
            'excluded_dates.*' => 'date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for conflicts if time-related fields are being updated
        if ($request->has(['day_of_week', 'shift_period', 'start_time', 'end_time'])) {
            $dayOfWeek = $request->day_of_week ?? $schedule->day_of_week;
            $shiftPeriod = $request->shift_period ?? $schedule->shift_period;
            $startTime = $request->start_time ?? $schedule->start_time;
            $endTime = $request->end_time ?? $schedule->end_time;

            if (Schedule::hasConflict(
                $schedule->doctor_id,
                $dayOfWeek,
                $shiftPeriod,
                $startTime,
                $endTime,
                $schedule->id
            )) {
                return response()->json([
                    'error' => 'Schedule conflict detected. This time slot overlaps with an existing schedule.',
                ], 422);
            }
        }

        try {
            $schedule->update(array_merge(
                $request->only([
                    'day_of_week', 'shift_period', 'start_time', 'end_time',
                    'number_of_patients_per_day', 'break_duration', 'break_times',
                    'excluded_dates', 'is_active',
                ]),
                ['updated_by' => Auth::id()]
            ));

            return response()->json([
                'message' => 'Schedule updated successfully',
                'schedule' => new ScheduleResource($schedule->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update schedule',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a schedule
     */
    public function destroy($id)
    {
        $schedule = Schedule::find($id);

        if (! $schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        try {
            $schedule->delete();

            return response()->json([
                'message' => 'Schedule deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete schedule',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update schedules for a doctor
     */
    public function updateSchedule(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'frequency' => 'string|in:Daily,Weekly,Monthly',
            'patients_based_on_time' => 'boolean',
            'time_slot' => 'nullable|integer',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.number_of_patients_per_day' => 'required|integer|min:1',
            'schedules.*.break_duration' => 'nullable|integer|min:5|max:120',
            'schedules.*.break_times' => 'nullable|array',
            'customDates.*.date' => 'required|date',
            'customDates.*.morningStartTime' => 'nullable|required_with:customDates.*.morningEndTime|date_format:H:i',
            'customDates.*.morningEndTime' => 'nullable|required_with:customDates.*.morningStartTime|date_format:H:i|after:customDates.*.morningStartTime',
            'customDates.*.afternoonStartTime' => 'nullable|required_with:customDates.*.afternoonEndTime|date_format:H:i',
            'customDates.*.afternoonEndTime' => 'nullable|required_with:customDates.*.afternoonStartTime|date_format:H:i|after:customDates.*.afternoonStartTime',
            'customDates.*.morningPatients' => 'nullable|required_with:customDates.*.morningStartTime|integer|min:1',
            'customDates.*.afternoonPatients' => 'nullable|required_with:customDates.*.afternoonStartTime|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request, $doctorId) {
                $doctor = Doctor::findOrFail($doctorId);

                // Update doctor scheduling preferences
                $doctor->update([
                    'frequency' => $request->frequency,
                    'patients_based_on_time' => $request->patients_based_on_time,
                    'time_slot' => $request->time_slot,
                ]);

                // Soft delete existing schedules
                $doctor->schedules()->delete();

                // Create new schedules based on frequency
                if ($request->frequency === 'Monthly' && $request->has('customDates')) {
                    $this->createCustomSchedules($request, $doctor);
                } elseif ($request->has('schedules')) {
                    $this->createRegularSchedules($request, $doctor);
                }

                return response()->json([
                    'message' => 'Doctor schedules updated successfully!',
                    'schedules' => ScheduleResource::collection($doctor->fresh()->schedules()->active()->get()),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error updating doctor schedule: '.$e->getMessage());

            return response()->json([
                'message' => 'Error updating doctor schedule',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available time slots for a doctor on a specific date
     */
    public function getAvailableSlots(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = Carbon::parse($request->date);
        $dayOfWeek = strtolower($date->format('l'));

        $schedules = Schedule::active()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->get();

        $availableSlots = [];

        foreach ($schedules as $schedule) {
            if (! $schedule->isDateExcluded($date)) {
                $slots = $schedule->getAvailableTimeSlots($date);
                $availableSlots[$schedule->shift_period] = $slots;
            }
        }

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'day_of_week' => $dayOfWeek,
            'available_slots' => $availableSlots,
        ]);
    }

    /**
     * Toggle schedule active status
     */
    public function toggleStatus($id)
    {
        $schedule = Schedule::find($id);

        if (! $schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        try {
            $schedule->update([
                'is_active' => ! $schedule->is_active,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Schedule status updated successfully',
                'schedule' => new ScheduleResource($schedule->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update schedule status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function createCustomSchedules(Request $request, Doctor $doctor)
    {
        $customSchedules = [];

        foreach ($request->customDates as $dateInfo) {
            // Handle morning shift
            if (! empty($dateInfo['morningStartTime'])) {
                $customSchedules[] = $this->prepareScheduleData(
                    $doctor,
                    Carbon::parse($dateInfo['date']),
                    'morning',
                    $dateInfo['morningStartTime'],
                    $dateInfo['morningEndTime'],
                    strtolower(Carbon::parse($dateInfo['date'])->format('l')),
                    $dateInfo['morningPatients'],
                    $request
                );
            }

            // Handle afternoon shift
            if (! empty($dateInfo['afternoonStartTime'])) {
                $customSchedules[] = $this->prepareScheduleData(
                    $doctor,
                    Carbon::parse($dateInfo['date']),
                    'afternoon',
                    $dateInfo['afternoonStartTime'],
                    $dateInfo['afternoonEndTime'],
                    strtolower(Carbon::parse($dateInfo['date'])->format('l')),
                    $dateInfo['afternoonPatients'],
                    $request
                );
            }
        }

        if (! empty($customSchedules)) {
            Schedule::insert($customSchedules);
        }
    }

    private function createRegularSchedules(Request $request, Doctor $doctor)
    {
        $schedules = collect($request->schedules)->map(function ($schedule) use ($doctor) {
            return [
                'doctor_id' => $doctor->id,
                'day_of_week' => $schedule['day_of_week'],
                'shift_period' => $schedule['shift_period'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'number_of_patients_per_day' => $schedule['number_of_patients_per_day'],
                'break_duration' => $schedule['break_duration'] ?? null,
                'break_times' => $schedule['break_times'] ?? null,
                'is_active' => true,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        Schedule::insert($schedules);
    }

    private function prepareScheduleData(Doctor $doctor, Carbon $date, string $shift, string $startTime, string $endTime, string $dayOfWeek, ?int $patients, Request $request): array
    {
        // Calculate number of patients based on scheduling type
        if ($patients !== null) {
            $numberOfPatients = $patients;
        } elseif ($request->patients_based_on_time && ! empty($request->time_slot)) {
            $numberOfPatients = $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot);
        } else {
            $numberOfPatients = $request->number_of_patients_per_day ?? 10;
        }

        return [
            'doctor_id' => $doctor->id,
            'date' => $date->format('Y-m-d'),
            'shift_period' => $shift,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'day_of_week' => $dayOfWeek,
            'is_active' => true,
            'number_of_patients_per_day' => $numberOfPatients,
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function calculatePatientsPerShift($startTime, $endTime, $timeSlot)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $totalMinutes = $end->diffInMinutes($start);

        return (int) floor($totalMinutes / $timeSlot);
    }
}
