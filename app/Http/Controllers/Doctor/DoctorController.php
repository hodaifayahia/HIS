<?php

namespace App\Http\Controllers\Doctor;

use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\Appointment;
use App\Models\AppointmentForcer;
use App\Models\Attribute;
use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\Folder;
use App\Models\MedicationDoctorFavorat;
use App\Models\Placeholder;
use App\Models\PlaceholderTemplate;
use App\Models\Schedule;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DoctorController extends Controller
{
    /**
     * Clear all availability-related cache for a specific doctor
     */
    private function clearDoctorAvailabilityCache(int $doctorId): void
    {
        // Clear the availability data cache
        \Cache::forget("doctor_availability_data_{$doctorId}");

        // Clear working hours cache for the next 30 days
        $startDate = now();
        $endDate = now()->addDays(30);

        while ($startDate <= $endDate) {
            $dateStr = $startDate->format('Y-m-d');
            \Cache::forget("doctor_{$doctorId}_hours_{$dateStr}");
            \Cache::forget("booked_slots_{$doctorId}_{$dateStr}");
            \Cache::forget("request_working_hours_{$doctorId}_{$dateStr}");
            $startDate->addDay();
        }
    }

    public function index(Request $request)
    {
        // Get the filter query parameters
        $filter = $request->query('query');
        $doctorId = $request->query('doctor_id');

        // Get the authenticated user
        $user = Auth::user();

        // Base query for doctors with eager loading
        $doctorsQuery = Doctor::with([
            'user:id,name,email,avatar,is_active', // Load only necessary fields
            'specialization:id,name,is_active',
            'schedules',
            'appointmentAvailableMonths',
            'appointmentForce',
        ]);

        // Conditionally apply the user role and active status filter
        // Admins/SuperAdmins see all doctors, others only see active 'doctor' role users
        if ($user && in_array($user->role, ['admin', 'SuperAdmin'])) {
            // For admin/SuperAdmin, just ensure a user exists (or remove this whereHas if you don't care if a doctor has no user)
            $doctorsQuery->whereHas('user');
        } else {
            // For other users, apply the specific role and active status filters
            $doctorsQuery->whereHas('user', function ($query) {
                $query->where('role', 'doctor')
                    ->where('is_active', true);
            });
        }

        // Apply filters ($filter and $doctorId) based on the user's role
        // This part remains the same as our previous discussion
        if ($user && ! in_array($user->role, ['admin', 'SuperAdmin'])) {
            if ($filter) {
                // Assuming $filter is the specialization ID
                $doctorsQuery->where('specialization_id', $filter);
            }

            if ($doctorId) {
                $doctorsQuery->where('id', $doctorId);
            }
        }

        // Paginate the results efficiently
        $doctors = $doctorsQuery->paginate(30); // Adjust per page as needed

        return DoctorResource::collection($doctors);
    }

    public function WorkingDates(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctorId' => 'nullable|exists:doctors,id',
                'month' => 'required|date_format:Y-m',
            ]);

            $startDate = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Modified query to ensure we get doctor name and specialization
            $doctorsQuery = Doctor::query()
                ->select([
                    'doctors.id',
                    'doctors.specialization_id',
                    'users.name as doctor_name',  // Changed from users.name to doctor_name alias
                    'specializations.name as specialization_name',  // Changed from specializations.name to specialization_name alias
                ])
                ->join('users', 'doctors.user_id', '=', 'users.id')
                ->join('specializations', 'doctors.specialization_id', '=', 'specializations.id')
                ->where('users.role', 'doctor');

            if (isset($validated['doctorId'])) {
                $doctorsQuery->where('doctors.id', $validated['doctorId']);
            }

            $doctors = $doctorsQuery->get();

            if ($doctors->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'month' => $validated['month'],
                    'total_doctors' => 0,
                ]);
            }

            $doctorIds = $doctors->pluck('id')->toArray();

            // Get active schedules
            $schedules = DB::table('schedules')
                ->select(['doctor_id', 'date', 'day_of_week', 'is_active'])
                ->where('is_active', true)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])
                        ->orWhereNull('date');
                })
                ->whereIn('doctor_id', $doctorIds)
                ->get();

            // Get appointments
            $appointments = DB::table('appointments')
                ->select(['doctor_id', 'appointment_date'])
                ->whereIn('doctor_id', $doctorIds)
                ->whereBetween('appointment_date', [$startDate, $endDate])
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                ->where('deleted_at', null)
                ->distinct()
                ->get();

            // Get excluded dates
            $excludedDates = ExcludedDate::where(function ($query) use ($doctorIds) {
                $query->whereIn('doctor_id', $doctorIds)
                    ->where('exclusionType', 'complete')
                    ->orWhereNull('doctor_id');
            })
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->get();

            // Get month availability

            $result = $doctors->map(function ($doctor) use ($schedules, $appointments, $excludedDates, $startDate, $endDate) {
                // Ensure doctor name and specialization exist
                if (! $doctor->doctor_name || ! $doctor->specialization_name) {
                    return null;
                }

                // Filter excluded dates for this doctor
                $doctorExcludedDates = $excludedDates->filter(function ($excludedDate) use ($doctor) {
                    return $excludedDate->doctor_id === $doctor->id || is_null($excludedDate->doctor_id);
                })->values();

                // Get doctor's schedules and appointments
                $doctorSchedules = $schedules->where('doctor_id', $doctor->id);
                $doctorAppointments = $appointments->where('doctor_id', $doctor->id);

                // Calculate working dates
                $workingDates = $this->calculateWorkingDatesOptimized(
                    $doctor->id,
                    $doctorSchedules,
                    $doctorAppointments,
                    $doctorExcludedDates,
                    $startDate,
                    $endDate
                );

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->doctor_name,
                    'specialization' => $doctor->specialization_name,
                    'excludedDates' => $doctorExcludedDates,
                    'working_dates' => array_values(array_unique($workingDates)),
                ];
            })->filter(); // Remove any null values

            return response()->json([
                'data' => $result,
                'month' => $validated['month'],
                'total_doctors' => $result->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in WorkingDates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error fetching working dates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateWorkingDatesOptimized($doctorId, $schedules, $appointments, $excludedDates, $startDate, $endDate)
    {
        $workingDates = [];
        $currentDate = $startDate->copy();

        $doctorExcludedDates = $excludedDates->filter(function ($date) use ($doctorId) {
            return $date->doctor_id === $doctorId || $date->doctor_id === null;
        });

        // Process scheduled dates
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            $isExcluded = $doctorExcludedDates->contains(function ($excludedDate) use ($currentDate) {
                $endDate = $excludedDate->end_date ?? $excludedDate->start_date;

                return $currentDate->between($excludedDate->start_date, $endDate);
            });

            if (! $isExcluded) {
                $hasSpecificSchedule = $schedules->contains(function ($schedule) use ($dateStr) {
                    return $schedule->date === $dateStr && $schedule->is_active;
                });

                $hasRecurringSchedule = $schedules->contains(function ($schedule) use ($currentDate) {
                    return $schedule->day_of_week === $currentDate->dayOfWeek
                           && $schedule->date === null
                           && $schedule->is_active;
                });

                if ($hasSpecificSchedule || $hasRecurringSchedule) {
                    $workingDates[] = $dateStr;
                }
            }

            $currentDate->addDay();
        }

        // Process appointment dates - ensure they're added even if not in a regular schedule
        $appointmentDates = $appointments
            ->where('doctor_id', $doctorId)
            ->map(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            })
            ->toArray();

        // Combine and remove duplicates
        return array_values(array_unique(array_merge($workingDates, $appointmentDates)));
    }

    public function getspecific()
    {
        try {
            // Check if user is authenticated
            if (! Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $user = Auth::user();

            // Check if the authenticated user has a doctor profile
            if (! $user->doctor) {
                // Return empty doctor data instead of error for non-doctor users
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'User does not have a doctor profile',
                ], 200);
            }

            $doctor = Doctor::with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce'])
                ->where('id', $user->doctor->id)
                ->firstOrFail();

            return new DoctorResource($doctor);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching doctor profile: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching doctor profile',
            ], 500);
        }
    }

    public function getDoctor(Request $request, $id = null)
    {
        // If an ID is provided, return the specific doctor
        if ($id) {
            $doctor = Doctor::whereHas('user', function ($query) {
                $query->where('role', 'doctor');
            })
                ->with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce']) // Load related data
                ->find($id); // Find the doctor by ID

            if (! $doctor) {
                return response()->json(['message' => 'Doctor not found'], 404);
            }

            return new DoctorResource($doctor); // Return a single doctor resource
        }

        // If no ID is provided, return a paginated list of doctors
        $filter = $request->query('query');
        $doctorsQuery = Doctor::whereHas('user', function ($query) {
            $query->where('role', 'doctor');
        })
            ->with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce']); // Add 'schedules'

        if ($filter) {
            $doctorsQuery->where('specialization_id', $filter);
        }

        $doctors = $doctorsQuery->paginate();

        return DoctorResource::collection($doctors); // Return a collection of doctors
    }
    // public function getDoctor($doctorid)
    // {
    //     $doctor = Doctor::find($doctorid);

    //     return DoctorResource::collection($doctor);
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Clear optimization cache on create
        \Artisan::call('optimize:clear');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required',
            'phone' => 'nullable|string',
            'password' => 'required|min:8',
            'is_active' => 'required|boolean',
            'allowed_appointment_today' => 'required|boolean',
            'include_time' => 'nullable|boolean',
            'specialization' => 'required|exists:specializations,id',
            'frequency' => 'required|string',
            'patients_based_on_time' => 'required|boolean',
            'time_slot' => 'required_if:patients_based_on_time,true|nullable|integer',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
            'schedules.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
            'customDates.*.date' => 'required|date',
            'customDates.*.shift_period' => 'required|in:morning,afternoon',
            'customDates.*.start_time' => 'required',
            'customDates.*.end_time' => 'required|after:customDates.*.start_time',
            'customDates.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'appointmentBookingWindow' => 'required|array',
            'appointmentBookingWindow.*.month' => 'required|integer|between:1,12',
            'appointmentBookingWindow.*.is_available' => 'required|boolean',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $avatarPath = null;
                if ($request->hasFile('avatar')) {
                    $avatar = $request->file('avatar');
                    $fileName = $request->name.'-'.time().'.'.$avatar->getClientOriginalExtension();
                    $avatarPath = $avatar->storeAs('avatar', $fileName, 'public');
                }

                // Create the user
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'created_by' => Auth::id(),
                    'password' => Hash::make($request->password),
                    'avatar' => $avatarPath,
                    'is_active' => $request->is_active,
                    'role' => 'doctor',
                ]);

                // Create the doctor
                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'specialization_id' => $request->specialization,
                    'created_by' => Auth::id(),
                    'frequency' => $request->frequency,
                    'allowed_appointment_today' => $request->allowed_appointment_today,
                    'patients_based_on_time' => $request->patients_based_on_time,
                    'time_slots' => $request->time_slot,
                    'include_time' => $request->include_time,

                ]);

                AppointmentForcer::create([
                    'start_time' => $request->start_time_force,
                    'doctor_id' => $doctor->id,
                    'end_time' => $request->end_time_force,
                    'number_of_patients' => $request->number_of_patients,
                ]);
                // Handle appointment booking window
                $appointmentMonths = [];
                $currentYear = date('Y');
                $currentMonth = date('n');

                if ($request->has('appointmentBookingWindow')) {
                    foreach ($request->appointmentBookingWindow as $month) {
                        $year = $currentYear;
                        if ($month['month'] < $currentMonth) {
                            $year = $currentYear + 1;
                        }

                        $appointmentMonths[] = [
                            'month' => $month['month'],
                            'year' => $year,
                            'doctor_id' => $doctor->id,
                            'is_available' => $month['is_available'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    DB::table('appointment_available_month')->insert($appointmentMonths);
                }

                // Handle schedules
                if ($request->has('customDates')) {
                    $this->createCustomSchedules($request, $doctor);
                } elseif ($request->has('schedules')) {
                    $this->createRegularSchedules($request, $doctor);
                }

                return response()->json([
                    'message' => 'Doctor, schedules, and appointment months created successfully!',
                    'doctor' => new DoctorResource($doctor),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating doctor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createCustomSchedules(Request $request, Doctor $doctor)
    {
        // Clear doctor-specific availability cache
        $this->clearDoctorAvailabilityCache($doctor->id);

        // Group custom schedules by date to process all shifts for the same date together
        $schedulesByDate = collect($request->customDates)
            ->groupBy('date')
            ->map(function ($dateSchedules, $date) use ($doctor, $request) {
                // Transform each schedule and calculate patient capacity
                $processedSchedules = $dateSchedules->map(function ($dateInfo) use ($doctor, $request) {
                    $parsedDate = Carbon::parse($dateInfo['date']);
                    $dayOfWeek = strtolower($parsedDate->format('l'));

                    // Make sure time formats are correct (H:i)
                    $startTime = $this->formatTimeString($dateInfo['start_time']);
                    $endTime = $this->formatTimeString($dateInfo['end_time']);

                    $numberOfPatients = $request->patients_based_on_time
                        ? $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot)
                        : $dateInfo['number_of_patients_per_day'];

                    return [
                        'doctor_id' => $doctor->id,
                        'date' => $parsedDate->format('Y-m-d'),
                        'day_of_week' => $dayOfWeek,
                        'shift_period' => $dateInfo['shift_period'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'number_of_patients_per_day' => $numberOfPatients,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                // For each date, update future appointments once with all shifts combined
                $this->updateFutureAppointmentsForEntireDate(
                    $doctor->id,
                    $date,
                    $dateSchedules->toArray(),
                    $request->patients_based_on_time,
                    $request->time_slot
                );

                return $processedSchedules;
            })
            ->flatten(1)
            ->all();

        if (! empty($schedulesByDate)) {
            Schedule::insert($schedulesByDate);
        }
    }
    // ... existing code ...

    /**
     * Update appointments for a specific date considering all shifts
     */
    private function updateFutureAppointmentsForEntireDate(
        int $doctorId,
        string $date,
        array $shiftsForDate,
        bool $patientsBasedOnTime,
        ?int $timeSlot = null
    ): void {
        try {
            DB::beginTransaction();

            // Parse the date string to Carbon instance
            $appointmentDate = Carbon::parse($date);

            // Build the query for future appointments on this specific date
            $query = Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $appointmentDate)
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value);

            // Get appointments for this date, ordered by their creation date (oldest first)
            $dateAppointments = $query->orderBy('appointment_time', 'asc')->get();

            if ($dateAppointments->isEmpty()) {
                DB::commit();

                return;
            }

            // Generate all time slots for the entire day, considering all shifts
            $allDaySlots = $this->getAllDaySlotsForDoctor($doctorId, $appointmentDate->format('Y-m-d'), $shiftsForDate, $patientsBasedOnTime, $timeSlot);

            if (empty($allDaySlots)) {
                DB::commit();

                return; // No slots available for this date
            }

            // Assign appointments to slots in order (first booked appointment gets first slot)
            foreach ($dateAppointments as $index => $appointment) {
                if ($index < count($allDaySlots)) {
                    // Assign the current slot to this appointment
                    $appointment->update([
                        'appointment_time' => $allDaySlots[$index],
                    ]);
                } else {
                    // Handle overflow appointments
                    $lastSlot = end($allDaySlots);
                    $lastSlotCarbon = Carbon::parse($appointmentDate->format('Y-m-d').' '.$lastSlot);

                    $appointmentDuration = 15; // in minutes, adjust based on your system
                    $newSlotTime = $lastSlotCarbon->addMinutes($appointmentDuration)->format('H:i:s');

                    $appointment->update([
                        'appointment_time' => $newSlotTime,
                    ]);

                    $allDaySlots[] = $newSlotTime;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ... existing code ...
    private function createRegularSchedules(Request $request, Doctor $doctor)
    {
        // Group schedules by day_of_week to process all shifts for the same day together
        $schedulesByDay = collect($request->schedules)
            ->groupBy('day_of_week')
            ->map(function ($daySchedules, $dayOfWeek) use ($doctor, $request) {
                // Transform each schedule and calculate patient capacity
                $processedSchedules = $daySchedules->map(function ($schedule) use ($doctor, $request) {
                    $numberOfPatients = $request->patients_based_on_time
                        ? $this->calculatePatientsPerShift($schedule['start_time'], $schedule['end_time'], $request->time_slot)
                        : $schedule['number_of_patients_per_day'];

                    // dd($numberOfPatients);
                    return [
                        'doctor_id' => $doctor->id,
                        'day_of_week' => $schedule['day_of_week'],
                        'shift_period' => $schedule['shift_period'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'number_of_patients_per_day' => $numberOfPatients,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                // For each day of the week, update future appointments once with all shifts combined
                $this->updateFutureAppointmentsForEntireDay(
                    $doctor->id,
                    $dayOfWeek,
                    $daySchedules->toArray(),
                    $request->patients_based_on_time,
                    $request->time_slot
                );

                return $processedSchedules;
            })
            ->flatten(1)
            ->all();

        Schedule::insert($schedulesByDay);
    }

    /**
     * Update appointments for an entire day considering all shifts
     */
    private function updateFutureAppointmentsForEntireDay(
        int $doctorId,
        string $dayOfWeek,
        array $shiftsForDay,
        bool $patientsBasedOnTime,
        ?int $timeSlot = null
    ): void {
        try {
            DB::beginTransaction();

            // Build the query for future appointments on this day of week
            $query = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', '>=', Carbon::today())
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value)
                ->whereRaw('LOWER(DAYNAME(appointment_date)) = ?', [strtolower($dayOfWeek)]);

            // Get all affected dates
            $affectedDates = $query->clone()
                ->select('appointment_date')
                ->distinct()
                ->orderBy('appointment_date')
                ->pluck('appointment_date');

            // Process each affected date
            foreach ($affectedDates as $date) {
                // Get appointments for this date, ordered by their creation date (oldest first)
                $dateAppointments = $query->clone()
                    ->whereDate('appointment_date', $date)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($dateAppointments->isEmpty()) {
                    continue;
                }

                // Generate all time slots for the entire day, considering all shifts
                $allDaySlots = $this->getAllDaySlotsForDoctor($doctorId, $date, $shiftsForDay, $patientsBasedOnTime, $timeSlot);

                if (empty($allDaySlots)) {
                    continue; // No slots available for this date
                }

                // Assign appointments to slots in order (first booked appointment gets first slot)
                foreach ($dateAppointments as $index => $appointment) {
                    if ($index < count($allDaySlots)) {
                        // Assign the current slot to this appointment
                        $appointment->update([
                            'appointment_time' => $allDaySlots[$index],
                        ]);
                    } else {
                        // Handle overflow appointments
                        $lastSlot = end($allDaySlots);
                        $lastSlotCarbon = Carbon::parse($date.' '.$lastSlot);

                        $appointmentDuration = 15; // in minutes, adjust based on your system
                        $newSlotTime = $lastSlotCarbon->addMinutes($appointmentDuration)->format('H:i:s');

                        $appointment->update([
                            'appointment_time' => $newSlotTime,
                        ]);

                        $allDaySlots[] = $newSlotTime;
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update future appointments for entire day: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate all time slots for a doctor's entire working day
     */
    private function getAllDaySlotsForDoctor(
        int $doctorId,
        string $date,
        array $shiftsForDay,
        bool $patientsBasedOnTime,
        ?int $timeSlot = null
    ): array {
        $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);
        $allDaySlots = [];

        // Sort shifts chronologically by start_time
        usort($shiftsForDay, function ($a, $b) {
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });

        // Process each shift and collect all slots for the day
        foreach ($shiftsForDay as $shift) {
            $startTime = $shift['start_time'];
            $endTime = $shift['end_time'];
            $numberOfPatients = $patientsBasedOnTime
                ? $this->calculatePatientsPerShift($startTime, $endTime, $timeSlot ?? $doctor->time_slot)
                : $shift['number_of_patients_per_day'];

            // Get slots for this specific shift
            $shiftSlots = $this->getDoctorWorkingHours(
                $doctorId,
                $date,
                $startTime,
                $endTime,
                $numberOfPatients,
                $shift['shift_period']
            );

            // Add to the collection of all day slots
            $allDaySlots = array_merge($allDaySlots, $shiftSlots);
        }

        // Sort all slots chronologically
        sort($allDaySlots);

        return array_unique($allDaySlots);
    }

    public function getDoctorWorkingHours(
        int $doctorId,
        string $date,
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $numberOfPatients = null,
        ?string $shiftPeriod = null
    ) {
        $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;
        $doctor = Doctor::find($doctorId, ['patients_based_on_time', 'time_slot']);

        // We're processing a specific shift with the provided parameters
        if ($doctor->time_slot !== null && (int) $doctor->time_slot > 0 && $doctor->patients_based_on_time) {
            $timeSlotMinutes = (int) $doctor->time_slot;

            $startTimeCopy = Carbon::parse($date.' '.$startTime);
            $endTimeCopy = Carbon::parse($date.' '.$endTime);
            $currentTime = clone $startTimeCopy;

            while ($currentTime < $endTimeCopy) {
                $workingHours[] = $currentTime->format('H:i');
                $currentTime->addMinutes($timeSlotMinutes);
            }
        } else {
            // Calculate based on number of patients
            $startTimeCopy = Carbon::parse($startTime);
            $endTimeCopy = Carbon::parse($endTime);

            if ($numberOfPatients <= 0) {
                return [];
            }

            // Calculate total duration in minutes
            $totalDuration = abs($endTimeCopy->diffInMinutes($startTimeCopy));

            if ($numberOfPatients > 1) {
                // Calculate the gap between each appointment
                // We subtract 1 from patients because we need one less gap than appointments
                // $gap = $totalDuration / ($numberOfPatients - 1);
                // $totalDuration = $totalDuration - $gap;
                // $gap = $totalDuration / ($numberOfPatients - 1);
                $gap = abs($totalDuration / ($numberOfPatients - 1));

                // Generate slots
                for ($i = 0; $i < $numberOfPatients; $i++) {
                    $minutesToAdd = round($i * $gap);
                    $slotTime = (clone $startTimeCopy)->addMinutes($minutesToAdd);
                    $workingHours[] = $slotTime->format('H:i');
                }
            } else {
                // Handle case with just one patient
                $workingHours[] = $startTimeCopy->format('H:i');
            }
        }

        return array_unique($workingHours);
    }

    // Helper function to ensure time format is H:i
    private function formatTimeString($timeString)
    {
        // Parse the input time string
        $time = Carbon::parse($timeString);

        // Return in the correct format
        return $time->format('H:i');
    }

    // This method needs to be fixed too to support proper time formatting
    private function calculatePatientsPerShift($startTime, $endTime, $timeSlot)
    {
        // Format the times to ensure correct format
        $startTime = $this->formatTimeString($startTime);
        $endTime = $this->formatTimeString($endTime);

        // Calculate total minutes in shift
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $totalMinutes = abs($end->diffInMinutes($start));

        // Calculate number of slots that can fit in this time period
        $slots = floor($totalMinutes / $timeSlot);

        // Return at least 1 patient
        return max(1, $slots);
    }

    private function prepareScheduleData(Doctor $doctor, Carbon $date, string $shift, string $startTime, string $endTime, string $dayOfWeek, ?int $patients, Request $request): array
    {
        $numberOfPatients = $patients;

        if ($request->patients_based_on_time && ! empty($request->time_slot)) {
            $numberOfPatients = $this->calculatePatientsPerShift($startTime, $endTime, $request->time_slot);
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
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Make sure your DoctorResource handles JSON days properly
    public function update(Request $request, $doctorid)
    {
        // Clear optimization cache on update
        \Artisan::call('optimize:clear');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required',
            'phone' => 'nullable|string',
            'password' => 'nullable|min:8',
            'allowed_appointment_today' => 'required|boolean',
            'is_active' => 'required|boolean',
            'start_time_force' => 'nullable|date_format:H:i',
            'end_time_force' => 'nullable|date_format:H:i|after:start_time',
            'number_of_patients' => 'nullable|min:1',
            'specialization' => 'required|exists:specializations,id',
            'frequency' => 'required|string',
            'include_time' => 'nullable|boolean',
            'patients_based_on_time' => 'required|boolean',
            'time_slot' => 'required_if:patients_based_on_time,true|nullable|integer',
            'schedules' => 'array|required_without:customDates',
            'customDates' => 'array|required_without:schedules',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
            'schedules.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
            'customDates.*.date' => 'required|date',
            'customDates.*.shift_period' => 'required|in:morning,afternoon',
            'customDates.*.start_time' => 'required',
            'customDates.*.end_time' => 'required|after:customDates.*.start_time',
            'customDates.*.number_of_patients_per_day' => 'required_if:patients_based_on_time,false|integer',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'appointmentBookingWindow' => 'required|array',
            'appointmentBookingWindow.*.month' => 'required|integer|between:1,12',
            'appointmentBookingWindow.*.is_available' => 'required|boolean',
        ]);
        try {
            return DB::transaction(function () use ($request, $doctorid) {
                // Find the doctor
                $doctor = Doctor::findOrFail($doctorid);
                // Check if the doctor has an associated user
                if (! $doctor->user) {
                    throw new \Exception('Doctor user not found');
                }
                if ($request->start_time_force && $request->end_time_force) {
                    $appointmentForce = AppointmentForcer::updateOrCreate(
                        ['doctor_id' => $doctor->id],
                        [
                            'start_time' => $request->start_time_force,
                            'end_time' => $request->end_time_force,
                            'number_of_patients' => $request->number_of_patients,
                        ]
                    );
                }

                // Handle avatar update
                $avatarPath = $doctor->user->avatar ?? null;
                if ($request->hasFile('avatar')) {
                    // Store new avatar
                    $avatar = $request->file('avatar');
                    $fileName = $request->name.'-'.time().'.'.$avatar->getClientOriginalExtension();
                    $newAvatarPath = $avatar->storeAs('avatar', $fileName, 'public');

                    // Delete old avatar if exists
                    if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                        Storage::disk('public')->delete($avatarPath);
                    }

                    $avatarPath = $newAvatarPath;
                }

                // Update user information
                $doctor->user->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'avatar' => $avatarPath,
                    'is_active' => $request->is_active,
                ]);

                // Update password if provided
                if ($request->filled('password')) {
                    $doctor->user->update([
                        'password' => Hash::make($request->password),
                    ]);
                }

                // Update doctor information
                $doctor->update([
                    'specialization_id' => $request->specialization,
                    'frequency' => $request->frequency,
                    'allowed_appointment_today' => $request->allowed_appointment_today,
                    'patients_based_on_time' => $request->patients_based_on_time,
                    'time_slot' => $request->time_slot,
                    'include_time' => $request->include_time,
                ]);

                // Handle appointment booking window
                if ($request->has('appointmentBookingWindow')) {
                    // Delete existing appointment months
                    DB::table('appointment_available_month')->where('doctor_id', $doctor->id)->delete();

                    // Prepare new appointment months data for bulk insertion
                    $appointmentMonths = [];
                    $currentYear = date('Y'); // Get current year
                    $currentMonth = date('n'); // Get current month without leading zeros

                    foreach ($request->appointmentBookingWindow as $month) {
                        $year = $currentYear;
                        if ($month['month'] < $currentMonth) {
                            $year = $currentYear + 1; // If the month has passed, set the year to next year
                        }

                        $appointmentMonths[] = [
                            'month' => $month['month'],
                            'year' => $year, // Add the year
                            'doctor_id' => $doctor->id,
                            'is_available' => $month['is_available'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Insert all appointment months in one query
                    DB::table('appointment_available_month')->insert($appointmentMonths);
                }

                // Delete existing schedules
                $doctor->schedules()->delete();

                // Create new schedules
                if ($request->has('customDates')) {
                    $this->createCustomSchedules($request, $doctor);
                } elseif ($request->has('schedules')) {
                    $this->createRegularSchedules($request, $doctor);
                }

                // Load fresh data with relationships
                $doctor->load(['user', 'schedules', 'specialization']);

                return response()->json([
                    'message' => 'Doctor and schedules updated successfully!',
                    'doctor' => new DoctorResource($doctor),
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error updating doctor: '.$e->getMessage());

            return response()->json([
                'message' => 'Error updating doctor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function GetDoctorsBySpecilaztion($specializationId)
    {

        $doctors = Doctor::with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce'])->where('specialization_id', $specializationId)->get();

        return DoctorResource::collection($doctors);
    }

    public function GetDoctorsBySpecilaztionsthisisfortest($specializationId)
    {

        $doctors = Doctor::with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce'])->where('specialization_id', $specializationId)->get();

        return DoctorResource::collection($doctors);
    }

    public function search(Request $request)
    {
        $searchTerm = $request->query('query');

        // If search term is empty, return all doctors paginated
        if (empty($searchTerm)) {
            return DoctorResource::collection(
                Doctor::with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce']) // Load specialization too
                    ->orderBy('created_at', 'desc')
                    ->paginate()
            );
        }

        // Search across related tables (Doctor, User, Specialization)
        $doctors = Doctor::whereHas('specialization', function ($query) use ($searchTerm) {
            // Search in Specialization table
            $query->where('name', 'LIKE', "%{$searchTerm}%");
            $query->where('id', 'LIKE', $searchTerm);
        })
            ->orWhereHas('user', function ($query) use ($searchTerm) {
                // Search in User table
                $query->where('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            })
            ->with(['user', 'specialization', 'schedules']) // Eager load User and Specialization
            ->orderBy('created_at', 'desc')
            ->paginate();

        return DoctorResource::collection($doctors);
    }

    /**
     * Display the specified resource.
     */
    public function specificDoctor(Request $request, $doctorId)
    {
        // Retrieve the doctor using the `doctorid` parameter
        $doctor = Doctor::with(['user', 'specialization', 'schedules', 'appointmentAvailableMonths', 'appointmentForce'])->find($doctorId);

        if (! $doctor) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }

        // Return the formatted doctor data using a resource
        return new DoctorResource($doctor);
    }

    public function storeSchedules($id, Request $request)
    {

        // Validate the request
        $validatedData = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedules.*.shift_period' => 'required|in:morning,afternoon,evening',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
        ]);

        try {
            // Fetch the doctor by ID
            $doctor = Doctor::find($validatedData['doctor_id']);

            if (! $doctor) {
                return response()->json(['error' => 'Doctor not found'], 404);
            }

            // Create schedules
            foreach ($validatedData['schedules'] as $scheduleData) {
                $doctor->schedules()->create([
                    'doctor_id' => $scheduleData['doctor_id'],
                    'day_of_week' => $scheduleData['day_of_week'],
                    'shift_period' => $scheduleData['shift_period'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],

                ]);
            }

            return response()->json(['message' => 'Schedules created successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            // Fetch the doctor by ID from the request
            $doctor = Doctor::find($request->id);

            if (! $doctor) {
                return response()->json(['error' => 'Doctor not found'], 404);
            }

            // Get the user_id associated with the doctor
            $userId = $doctor->user_id;

            // Delete the doctor
            $doctor->delete();
            // Fetch the user by user_id
            $user = User::find($userId);

            if (! $user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Delete the user
            $user->delete();

            // Return no content response to signify successful operation
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            // Fetch the user_ids associated with the doctor IDs
            $userIds = Doctor::whereIn('id', $request->ids)->pluck('user_id');

            // Delete doctors
            $doctorsDeleted = Doctor::whereIn('id', $request->ids)->delete();

            // Delete users associated with the doctors
            $usersDeleted = User::whereIn('id', $userIds)->delete();

            // Return response with the count of deleted doctors and users
            return response()->json([
                'message' => "$doctorsDeleted doctors and $usersDeleted users deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting doctors and users'], 500);
        }
    }

    /**
     * Duplicate a doctor with all their schedules and configurations
     */
    public function duplicate(Request $request, $doctorId)
    {
        // Clear optimization cache
        \Artisan::call('optimize:clear');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Find the source doctor with all relationships
            $sourceDoctor = Doctor::with([
                'user',
                'schedules',
                'appointmentAvailableMonths',
                'appointmentForce',
                'excludedDates',
            ])->findOrFail($doctorId);

            // Create new user with provided credentials
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'doctor',
                'is_active' => $sourceDoctor->user->is_active ?? true,
            ]);

            // Duplicate doctor record with same configuration
            $newDoctor = Doctor::create([
                'user_id' => $newUser->id,
                'specialization_id' => $sourceDoctor->specialization_id,
                'time_slot' => $sourceDoctor->time_slot,
                'frequency' => $sourceDoctor->frequency,
                'patients_based_on_time' => $sourceDoctor->patients_based_on_time,
                'allowed_appointment_today' => $sourceDoctor->allowed_appointment_today ?? true,
                'is_active' => $sourceDoctor->is_active ?? true,
                'include_time' => $sourceDoctor->include_time ?? false,
                'appointment_booking_window' => $sourceDoctor->appointment_booking_window,
            ]);

            // Duplicate schedules (recurring schedules)
            if ($sourceDoctor->schedules->isNotEmpty()) {
                $schedules = $sourceDoctor->schedules->map(function ($schedule) use ($newDoctor) {
                    return [
                        'doctor_id' => $newDoctor->id,
                        'day_of_week' => $schedule->day_of_week,
                        'date' => $schedule->date,
                        'shift_period' => $schedule->shift_period,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'is_active' => $schedule->is_active ?? true,
                        'number_of_patients_per_day' => $schedule->number_of_patients_per_day,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                Schedule::insert($schedules);
            }

            // Duplicate appointment available months
            if ($sourceDoctor->appointmentAvailableMonths->isNotEmpty()) {
                $availableMonths = $sourceDoctor->appointmentAvailableMonths->map(function ($month) use ($newDoctor) {
                    return [
                        'doctor_id' => $newDoctor->id,
                        'month' => $month->month,
                        'year' => $month->year,
                        'is_available' => $month->is_available ?? true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                \App\Models\AppointmentAvailableMonth::insert($availableMonths);
            }

            // Duplicate appointment forcers (custom date schedules)
            if ($sourceDoctor->appointmentForce->isNotEmpty()) {
                $appointmentForcers = $sourceDoctor->appointmentForce->map(function ($forcer) use ($newDoctor) {
                    return [
                        'doctor_id' => $newDoctor->id,
                        'specific_date' => $forcer->specific_date,
                        'start_time' => $forcer->start_time,
                        'end_time' => $forcer->end_time,
                        'number_of_patients' => $forcer->number_of_patients,
                        'include_time' => $forcer->include_time ?? false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                AppointmentForcer::insert($appointmentForcers);
            }

            // Duplicate excluded dates
            if ($sourceDoctor->excludedDates->isNotEmpty()) {
                $excludedDates = $sourceDoctor->excludedDates
                    ->where('doctor_id', $sourceDoctor->id) // Only doctor-specific exclusions
                    ->map(function ($exclusion) use ($newDoctor) {
                        return [
                            'doctor_id' => $newDoctor->id,
                            'start_date' => $exclusion->start_date,
                            'end_date' => $exclusion->end_date,
                            'start_time' => $exclusion->start_time,
                            'end_time' => $exclusion->end_time,
                            'exclusionType' => $exclusion->exclusionType,
                            'number_of_patients_per_day' => $exclusion->number_of_patients_per_day,
                            'shift_period' => $exclusion->shift_period,
                            'is_active' => $exclusion->is_active ?? true,
                            'apply_for_all_years' => $exclusion->apply_for_all_years ?? false,
                            'reason' => $exclusion->reason,
                            'morning_start_time' => $exclusion->morning_start_time,
                            'morning_end_time' => $exclusion->morning_end_time,
                            'morning_patients' => $exclusion->morning_patients,
                            'is_morning_active' => $exclusion->is_morning_active ?? false,
                            'afternoon_start_time' => $exclusion->afternoon_start_time,
                            'afternoon_end_time' => $exclusion->afternoon_end_time,
                            'afternoon_patients' => $exclusion->afternoon_patients,
                            'is_afternoon_active' => $exclusion->is_afternoon_active ?? false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                if (! empty($excludedDates)) {
                    ExcludedDate::insert($excludedDates);
                }
            }

            // Duplicate consultation configurations
            $this->duplicateConsultationConfigurations($sourceDoctor->id, $newDoctor->id);

            DB::commit();

            // Clear the new doctor's availability cache
            $this->clearDoctorAvailabilityCache($newDoctor->id);

            return response()->json([
                'message' => 'Doctor duplicated successfully',
                'data' => new DoctorResource($newDoctor->load([
                    'user',
                    'specialization',
                    'schedules',
                    'appointmentAvailableMonths',
                    'appointmentForce',
                    'excludedDates',
                ])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Doctor duplication failed: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'message' => 'Failed to duplicate doctor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate consultation configurations (folders, placeholders, templates, medication favorites)
     * from source doctor to target doctor
     */
    private function duplicateConsultationConfigurations(int $sourceDoctorId, int $targetDoctorId): void
    {
        // 1. Duplicate Folders first (needed for templates)
        $sourceFolders = Folder::where('doctor_id', $sourceDoctorId)->get();
        $folderMap = []; // Map old folder IDs to new ones

        foreach ($sourceFolders as $sourceFolder) {
            $newFolder = Folder::create([
                'name' => $sourceFolder->name,
                'description' => $sourceFolder->description,
                'doctor_id' => $targetDoctorId,
            ]);

            $folderMap[$sourceFolder->id] = $newFolder->id;
        }

        // 2. Duplicate Placeholders (Sections) with their attributes
        $sourcePlaceholders = Placeholder::with('attributes')
            ->where('doctor_id', $sourceDoctorId)
            ->get();

        $placeholderMap = []; // Map old placeholder IDs to new ones for template associations
        $attributeMap = []; // Map old attribute IDs to new ones

        foreach ($sourcePlaceholders as $sourcePlaceholder) {
            $newPlaceholder = Placeholder::create([
                'name' => $sourcePlaceholder->name,
                'description' => $sourcePlaceholder->description,
                'doctor_id' => $targetDoctorId,
                'specializations_id' => $sourcePlaceholder->specializations_id,
            ]);

            // Map old to new placeholder ID
            $placeholderMap[$sourcePlaceholder->id] = $newPlaceholder->id;

            // Duplicate all attributes for this placeholder
            if ($sourcePlaceholder->attributes->isNotEmpty()) {
                foreach ($sourcePlaceholder->attributes as $attribute) {
                    $newAttribute = Attribute::create([
                        'placeholder_id' => $newPlaceholder->id,
                        'name' => $attribute->name,
                        'description' => $attribute->description ?? null,
                        'value' => $attribute->value,
                        'input_type' => $attribute->input_type ?? 'text',
                    ]);

                    // Map old attribute ID to new attribute ID
                    $attributeMap[$attribute->id] = $newAttribute->id;
                }
            }
        }

        // 3. Duplicate Templates with placeholder associations
        $sourceTemplates = Template::with('placeholders')
            ->where('doctor_id', $sourceDoctorId)
            ->get();

        foreach ($sourceTemplates as $sourceTemplate) {
            // Use mapped folder_id if folder was duplicated, otherwise keep original
            $newFolderId = isset($folderMap[$sourceTemplate->folder_id])
                ? $folderMap[$sourceTemplate->folder_id]
                : $sourceTemplate->folder_id;

            $newTemplate = Template::create([
                'name' => $sourceTemplate->name,
                'description' => $sourceTemplate->description,
                'content' => $sourceTemplate->content,
                'doctor_id' => $targetDoctorId,
                'mime_type' => $sourceTemplate->mime_type,
                'folder_id' => $newFolderId,
                'file_path' => $sourceTemplate->file_path,
            ]);

            // Duplicate placeholder-template associations
            if ($sourceTemplate->placeholders->isNotEmpty()) {
                $placeholderTemplates = [];

                foreach ($sourceTemplate->placeholders as $placeholder) {
                    // Only create association if placeholder was duplicated
                    if (isset($placeholderMap[$placeholder->id])) {
                        $oldAttributeId = $placeholder->pivot->attribute_id;
                        $newAttributeId = isset($attributeMap[$oldAttributeId])
                            ? $attributeMap[$oldAttributeId]
                            : $oldAttributeId;

                        $placeholderTemplates[] = [
                            'template_id' => $newTemplate->id,
                            'placeholder_id' => $placeholderMap[$placeholder->id],
                            'attribute_id' => $newAttributeId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (! empty($placeholderTemplates)) {
                    PlaceholderTemplate::insert($placeholderTemplates);
                }
            }
        }

        // 4. Duplicate Medication Favorites
        $sourceFavorites = MedicationDoctorFavorat::where('doctor_id', $sourceDoctorId)->get();

        if ($sourceFavorites->isNotEmpty()) {
            $favorites = $sourceFavorites->map(function ($favorite) use ($targetDoctorId) {
                return [
                    'medication_id' => $favorite->medication_id,
                    'doctor_id' => $targetDoctorId,
                    'favorited_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            MedicationDoctorFavorat::insert($favorites);
        }

        \Log::info('Consultation configurations duplicated successfully', [
            'source_doctor_id' => $sourceDoctorId,
            'target_doctor_id' => $targetDoctorId,
            'folders' => $sourceFolders->count(),
            'placeholders' => $sourcePlaceholders->count(),
            'templates' => $sourceTemplates->count(),
            'medication_favorites' => $sourceFavorites->count(),
        ]);
    }
}
