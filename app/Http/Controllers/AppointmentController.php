<?php

namespace App\Http\Controllers;

use App\AppointmentSatatusEnum;
use App\DayOfWeekEnum;
use App\Enum\AppointmentStatusEnum; // Make sure this enum is correctly namespaced
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentAvailableMonth;
use App\Models\AppointmentForcer;
use App\Models\Doctor;
use App\Models\ExcludedDate;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\WaitList;
use App\Models\Appointment\AppointmentPrestation;
use App\Models\CONFIGURATION\Prestation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; // Added: Required for conditional validation

class AppointmentController extends Controller
{
    // Private property to store pre-loaded availability data
    private $availabilityData;
    private $bookedSlotsCache = []; // New: In-request memoization for booked slots

    public $excludedStatuses = [
        AppointmentSatatusEnum::CANCELED->value, // Add CANCELED here
    ];

    private $statusLabels = [
        0 => 'Scheduled',
        1 => 'Confirmed',
        2 => 'Canceled',
        3 => 'Pending',
        4 => 'Done',
        5 => 'OnWorking'
    ];

    /**
     * Initializes pre-loaded availability data for a given doctor.
     * This method should be called once per request for performance.
     *
     * @param int $doctorId
     * @return void
     */
    private function initAvailabilityData(int $doctorId): void
    {
        // Use a static property for memoization within a single request instance
        if ($this->availabilityData !== null && $this->availabilityData->doctor->id === $doctorId) {
            return; // Already initialized for this doctor in this request
        }

        // Cache the entire availability data for a short period (e.g., 10 minutes)
        // This avoids re-fetching ALL configuration data for the same doctor across *different* requests
        $cacheKey = "doctor_availability_data_{$doctorId}";
        $this->availabilityData = Cache::remember($cacheKey, 1, function () use ($doctorId) {

            $doctor = Doctor::select('id', 'patients_based_on_time', 'time_slot')->findOrFail($doctorId);
            $now = Carbon::now();
            // Search up to 2 years out for schedules/months, adjust as needed
            $endOfSearchPeriod = $now->copy()->addYears(2)->endOfYear();

            // Pre-fetch all relevant data efficiently
            $availableMonths = AppointmentAvailableMonth::where('doctor_id', $doctorId)
                ->where('is_available', true)
                ->where(function ($query) use ($now, $endOfSearchPeriod) {
                    $query->where('year', '>=', $now->year)
                        ->where('year', '<=', $endOfSearchPeriod->year);
                })
                ->get()
                ->map(fn ($m) => Carbon::create($m->year, $m->month)->format('Y-m'))
                ->toArray();

            $allSchedules = Schedule::where('doctor_id', $doctorId)
                ->where('is_active', true)
                ->get();

            // Group recurring schedules by day of week (e.g., Monday, Tuesday)
            $schedulesByDay = $allSchedules->filter(fn ($s) => is_null($s->date))
                ->groupBy('day_of_week');
            // Key specific date schedules by their exact date string
            $schedulesByDate = $allSchedules->filter(fn ($s) => !is_null($s->date))
                ->keyBy(fn ($s) => Carbon::parse($s->date)->format('Y-m-d'));

            $excludedDates = ExcludedDate::where(function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId) // Excluded dates for this doctor
                    ->orWhereNull('doctor_id'); // Global excluded dates (where doctor_id is null)
            })->get();

            // Separate complete exclusions (blocking entire days)
            $completeExcludedRanges = $excludedDates->filter(fn ($ed) => $ed->exclusionType === 'complete')
                ->map(fn ($ed) => [
                    'start' => Carbon::parse($ed->start_date)->startOfDay(),
                    'end' => Carbon::parse($ed->end_date ?? $ed->start_date)->endOfDay()
                ])->toArray();

            // Store the full excluded date models for 'limited' types
            // These contain scheduling info that overrides regular schedules
            $limitedExcludedSchedules = $excludedDates->filter(fn ($ed) =>
                $ed->exclusionType === 'limited' && $ed->is_active === true && // Ensure 'is_active' is considered
                Carbon::parse($ed->end_date ?? $ed->start_date)->gte($now->startOfDay()) // Only active limited exclusions for future/current dates
            )->keyBy(fn ($ed) => Carbon::parse($ed->start_date)->format('Y-m-d'));

            return (object)[
                'doctor' => $doctor,
                'availableMonths' => $availableMonths,
                'schedulesByDay' => $schedulesByDay,
                'schedulesByDate' => $schedulesByDate,
                'completeExcludedRanges' => $completeExcludedRanges,
                'limitedExcludedSchedules' => $limitedExcludedSchedules,
            ];
        });
    }

    /**
     * Determines if a specific date is truly available for appointments, considering all rules.
     * This method uses the pre-loaded data from `initAvailabilityData`.
     *
     * @param Carbon $date
     * @return bool
     */
    private function isDateTrulyAvailable(Carbon $date): bool
    {
        $data = $this->availabilityData;
        $doctorId = $data->doctor->id;
        $dateString = $date->format('Y-m-d');
        $dayOfWeek = DayOfWeekEnum::cases()[$date->dayOfWeek]->value;

        // 1. Is in the past? (Past dates are never available for new bookings)
        if ($date->startOfDay()->lt(Carbon::now()->startOfDay())) {
            return false;
        }

        // 2. Is completely excluded? (Global or doctor-specific complete exclusions)
        foreach ($data->completeExcludedRanges as $range) {
            if ($date->between($range['start'], $range['end'])) {
                return false;
            }
        }

        // 3. Is month available? (Check against pre-loaded available months)
        if (!in_array($date->format('Y-m'), $data->availableMonths)) {
            return false;
        }

        // 4. Check schedules: Specific date schedules & limited exclusions take precedence over recurring day schedules.
        // The getDoctorWorkingHours method will handle the priority.
        // If getDoctorWorkingHours returns an empty array, it means no valid schedule or limited exclusion applies.
        $workingHours = $this->getDoctorWorkingHours($doctorId, $dateString);
        if (empty($workingHours)) {
            return false; // No working hours defined or derived for this date
        }

        // 5. Are there any free slots after accounting for already booked appointments?
        $bookedSlots = $this->getBookedSlots($doctorId, $dateString);
        return count(array_diff($workingHours, $bookedSlots)) > 0;
    }

    /**
     * Finds the next available appointment date starting from a given date.
     * Uses the pre-loaded availability data and optimized date iteration.
     *
     * @param Carbon $startDate
     * @return Carbon|null
     */
    private function findNextAvailableAppointment(Carbon $startDate): ?Carbon
    {
        $endOfSearchPeriod = Carbon::now()->copy()->addYears(2)->endOfYear(); // Search up to 2 years out
        $currentDate = $startDate->copy(); // Start searching from the given date

        while ($currentDate->lte($endOfSearchPeriod)) {
            // Optimization: If the current month is not available, jump to the start of the next month.
            // This relies on `availableMonths` being a simple Y-m array.
            if (!in_array($currentDate->format('Y-m'), $this->availabilityData->availableMonths)) {
                $currentDate->addMonthNoOverflow()->startOfMonth();
                continue; // Restart loop with the new date
            }

            if ($this->isDateTrulyAvailable($currentDate)) {
                return $currentDate; // Found an available date!
            }
            $currentDate->addDay(); // Move to the next day
        }

        return null; // No available appointment found within the search period
    }

    /**
     * Finds the next available appointment date within a given range (backward and forward).
     *
     * @param Carbon $startDate
     * @param int $range
     * @return Carbon|null
     */
    private function findNextAvailableAppointmentWithinRange(Carbon $startDate, int $range): ?Carbon
    {
        $availableDates = collect();
        $today = Carbon::now()->startOfDay();

        // Check backward from startDate to startDate - range days, excluding dates in the past
        for ($i = $range; $i > 0; $i--) {
            $checkDate = $startDate->copy()->subDays($i);
            if ($checkDate->lt($today)) {
                continue; // Skip dates in the past
            }
            if ($this->isDateTrulyAvailable($checkDate)) {
                $availableDates->push($checkDate);
            }
        }

        // Check startDate itself, if it's not in the past
        if ($startDate->gte($today) && $this->isDateTrulyAvailable($startDate)) {
            $availableDates->push($startDate);
        }

        // Check forward from startDate + 1 day to startDate + range days
        for ($i = 1; $i <= $range; $i++) {
            $forwardDate = $startDate->copy()->addDays($i);
            if ($this->isDateTrulyAvailable($forwardDate)) {
                $availableDates->push($forwardDate);
            }
        }

        // Sort dates and return the earliest one
        if ($availableDates->isNotEmpty()) {
            return $availableDates->sort()->first();
        }

        return null;
    }


    public function index(Request $request, $doctorId)
    {
        try {
            $query = Appointment::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'doctor:id,user_id,specialization_id',
                    'doctor.user:id,name',
                    'createdByUser:id,name',
                    'updatedByUser:id,name',
                    'canceledByUser:id,name',
                    'waitlist'
                ])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->where('doctor_id', $doctorId)
                ->whereNull('deleted_at');

            // Apply filters using scopes
            if ($request->status != AppointmentSatatusEnum::CANCELED->value) {
                $query->filterFuture();
            }

            $query->filterByStatus($request->status)
                ->when($request->filled('date'), function ($q) use ($request) {
                    return $q->filterByDate($request->date);
                })
                ->when($request->filter === 'today', function ($q) {
                    return $q->filterToday();
                });

            $query->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            $appointments = $query->paginate(30);

            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'last_page' => $appointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
public function bookSameDayAppointment(Request $request)
{
    try {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'appointment_time' => 'required',
            'prestation_id' => 'nullable|exists:prestations,id'
        ]);
        
        $doctor = Doctor::find($validated['doctor_id']);
        
        if (!$doctor || !$doctor->allowed_appointment_today) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor does not allow same-day appointments'
            ], 400);
        }
        
        $appointmentDateTime = Carbon::parse($validated['appointment_time']);
        $appointmentDate = $appointmentDateTime->format('Y-m-d');
        $appointmentTime = $appointmentDateTime->format('H:i');
        
        // Check if slot is still available using existing model method
        if (!Appointment::isSlotAvailable(
            $validated['doctor_id'],
            $appointmentDate,
            $appointmentTime,
            $this->excludedStatuses
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Selected time slot is no longer available'
            ], 400);
        }
        
        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 0, // Scheduled
            'notes' => 'Same-day appointment',
            'created_by' => Auth::id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Same-day appointment booked successfully',
            'appointment' => new AppointmentResource($appointment)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error booking appointment: ' . $e->getMessage()
        ], 500);
    }
}

    public function consulationappointment(Request $request, $doctorId)
    {
        try {
            $query = Appointment::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'doctor:id,user_id,specialization_id',
                    'doctor.user:id,name',
                    'createdByUser:id,name',
                    'updatedByUser:id,name',
                    'canceledByUser:id,name',
                    'waitlist'
                ])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->where('doctor_id', $doctorId)
                ->whereNull('deleted_at');

            // Handle multiple statuses or single status
            if ($request->has('statuses') && is_array($request->statuses)) {
                // Handle multiple statuses [0, 1, 4, 5]
                $statuses = $request->statuses;

                // Apply future filter for all statuses except ONWORKING (5) and DONE
                $query->where(function ($q) use ($statuses) {
                    foreach ($statuses as $status) {
                        $q->orWhere(function ($subQuery) use ($status) {
                            $subQuery->where('status', $status);

                            // ONWORKING (5) gets all appointments regardless of date
                            // DONE appointments should also show regardless of date (recently completed)
                            // For other statuses, apply future filter
                            if ($status != 5 && $status != AppointmentSatatusEnum::DONE->value) {
                                $subQuery->where(function ($dateQuery) {
                                    $dateQuery->where('appointment_date', '>', now()->toDateString())
                                        ->orWhere(function ($todayQuery) {
                                            $todayQuery->where('appointment_date', now()->toDateString())
                                                ->where('appointment_time', '>=', now()->toTimeString());
                                        });
                                });
                            }

                            // For DONE status, show only appointments completed today
                            if ($status == AppointmentSatatusEnum::DONE->value) {
                                // Show only appointments that were marked as DONE today
                                $subQuery->whereDate('updated_at', now()->toDateString());
                            }
                        });
                    }
                });
            } else {
                // Handle single status (existing logic with modifications)
                if ($request->status != AppointmentSatatusEnum::CANCELED->value) {
                    // Apply future filter only if status is not ONWORKING (5) and not DONE
                    if ($request->status != 5 && $request->status != AppointmentSatatusEnum::DONE->value) {
                        $query->filterFuture();
                    }

                    // For DONE status, show only appointments completed today
                    if ($request->status == AppointmentSatatusEnum::DONE->value) {
                        // Show only appointments marked as DONE today
                        $query->whereDate('updated_at', now()->toDateString());
                    }
                }

                $query->filterByStatus($request->status)
                    ->when($request->filled('date'), function ($q) use ($request) {
                        return $q->filterByDate($request->date);
                    })
                    ->when($request->filter === 'today', function ($q) {
                        return $q->filterToday();
                    });
            }

            // Apply search if provided
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('patient', function ($patientQuery) use ($searchTerm) {
                        $patientQuery->where('Firstname', 'like', "%{$searchTerm}%")
                            ->orWhere('Lastname', 'like', "%{$searchTerm}%")
                            ->orWhere('phone', 'like', "%{$searchTerm}%");
                    });
                });
            }

            // Modified ordering to show recently completed appointments first for DONE status
            if (
                $request->status == AppointmentSatatusEnum::DONE->value ||
                (is_array($request->statuses) && in_array(AppointmentSatatusEnum::DONE->value, $request->statuses))
            ) {
                $query->orderBy('updated_at', 'desc') // Recently completed first
                    ->orderBy('appointment_date', 'desc')
                    ->orderBy('appointment_time', 'desc');
            } else {
                $query->orderBy('appointment_date', 'asc')
                    ->orderBy('appointment_time', 'asc');
            }

            $appointments = $query->paginate(30);

            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'last_page' => $appointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateAppointmentsPdf(Request $request)
    {
        try {
            // Start building the query
            $query = Appointment::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'doctor:id,user_id,specialization_id',
                    'doctor.user:id,name'
                ])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->whereNull('deleted_at')
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            // Apply all possible filters
            $this->applyFilters($query, $request);

            // Fetch the filtered appointments
            $appointments = $query->get();
            // Get the doctor with include_time by matching user name
            $includeTime = (bool) (Doctor::whereHas('user', function ($q) use ($request) {
                $q->where('name', $request->doctorName);
            })->value('include_time'));


            // Transform appointments to include status labels
            $transformedAppointments = $appointments->map(function ($appointment) {
                $appointment->status_label = 'Unknown';
                return $appointment;
            });
            // Get the filter summary for the PDF header
            $filterSummary = $this->getFilterSummary($request);

            // Generate PDF with filter summary
            $pdf = Pdf::loadView('pdf.appointments', [
                'appointments' => $transformedAppointments,
                'filterSummary' => $filterSummary,
                'includeTime' => $includeTime, // <-- Pass to view
            ]);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'landscape');

            $doctorName = preg_replace('/[^A-Za-z0-9\-]/', '_', $request->doctorName); // Clean filename
            $date = now()->format('Y-m-d');
            $fileName = "appointments_{$doctorName}_{$date}.pdf";

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function applyFilters($query, Request $request)
    {
        // Patient Name Filter
        if ($request->filled('patientName')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where(function ($subQ) use ($request) {
                    $terms = explode(' ', $request->patientName);
                    foreach ($terms as $term) {
                        $subQ->where(function ($nameQ) use ($term) {
                            $nameQ->where('Firstname', 'like', '%' . $term . '%')
                                ->orWhere('Lastname', 'like', '%' . $term . '%');
                        });
                    }
                });
            });
        }

        // Phone Filter
        if ($request->filled('phone')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->phone . '%');
            });
        }

        // Date of Birth Filter
        if ($request->filled('dateOfBirth')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->whereDate('dateOfBirth', $request->dateOfBirth);
            });
        }

        // Appointment Date Filter
        if ($request->filled('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        // Appointment Time Filter
        if ($request->filled('time')) {
            $query->where('appointment_time', 'like', '%' . $request->time . '%');
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        // Doctor Name Filter
        if ($request->filled('doctorName')) {
            $query->whereHas('doctor.user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->doctorName . '%');
            });
        }
    }

    private function getFilterSummary(Request $request)
    {
        $summary = [];

        if ($request->filled('patientName')) {
            $summary[] = "Patient Name: " . $request->patientName;
        }
        if ($request->filled('phone')) {
            $summary[] = "Phone: " . $request->phone;
        }
        if ($request->filled('dateOfBirth')) {
            $summary[] = "Date of Birth: " . $request->dateOfBirth;
        }
        if ($request->filled('date')) {
            $summary[] = "Appointment Date: " . $request->date;
        }
        if ($request->filled('time')) {
            $summary[] = "Appointment Time: " . $request->time;
        }
        if ($request->filled('status') && $request->status !== 'ALL') {
            $summary[] = "Status: " . ($this->statusLabels[$request->status] ?? $request->status);
        }
        if ($request->filled('doctorName')) {
            $summary[] = "Doctor: " . $request->doctorName;
        }

        return $summary;
    }

    public function ForPatient(Request $request, $Patientid)
    {
        try {
            // Get appointments only for this patient
            $appointments = Appointment::query()
                ->with(['patient', 'doctor:id,user_id,specialization_id', 'doctor.user:id,name', 'waitlist'])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->where('patient_id', $Patientid)
                ->whereNull('deleted_at')
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            // Apply filters if provided
            if ($request->filled('status') && $request->status !== 'ALL') {
                $appointments->where('status', $request->status);
            }
            if ($request->filled('date')) {
                $appointments->whereDate('appointment_date', $request->date);
            }
            if ($request->filled('filter') && $request->filter === 'today') {
                $appointments->whereDate('appointment_date', now()->toDateString())
                    ->whereIn('status', [0, 1]);
            }

            // Fetch results without pagination
            $appointments = $appointments->get();

            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingAppointment(Request $request)
    {
        try {
            // Build a query to return only pending appointments (status = 3)
            $query = Appointment::query()
                ->with(['patient', 'doctor:id,user_id,specialization_id', 'doctor.user:id,name', 'waitlist'])
                ->whereHas('doctor', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereHas('user');
                })
                ->whereNull('deleted_at')
                ->where('status', 3) // Only return pending appointments (status PENDING = 3)
                ->orderBy('appointment_date', 'asc');

            // Correctly access doctorId from the request query parameters
            $doctorId = $request->query('doctorId');
            if ($doctorId) {
                $query->where('doctor_id', $doctorId);
            }
            $date = $request->query('date');
            if ($date) {
                $query->whereDate('appointment_date', $date);
            }

            // Paginate the results
            $appointments = $query->paginate(50);


            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
                'meta' => [
                    'current_page' => $appointments->currentPage(),
                    'per_page' => $appointments->perPage(),
                    'total' => $appointments->total(),
                    'last_page' => $appointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function GetAllAppointments(Request $request)
    {
        try {
            $query = DB::table('appointments')
                ->select([
                    'appointments.*',
                    'patients.firstname as patient_first_name',
                    'patients.lastname as patient_last_name',
                    'patients.id as patient_id', // Add this line
                    'patients.phone',
                    'patients.dateOfBirth as patient_Date_Of_Birth',
                    'users.name as doctor_name',
                    'doctors.id as doctor_id',
                    'doctors.specialization_id as specialization_id',
                    'appointments.status as appointment_status'
                ])
                ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
                ->join('users', 'doctors.user_id', '=', 'users.id')
                ->whereNull('appointments.deleted_at')
                ->whereNull('doctors.deleted_at')
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            if ($request->doctor_id) {
                $query->where('appointments.doctor_id', $request->doctor_id);
            }

            if ($request->filled('filter') && $request->filter === 'today') {
                $query->whereDate('appointment_date', now()->toDateString());
            }

            if ($request->filled('status')) {
                $query->where('appointments.status', $request->status);
            }

            if ($request->filled('patient_name')) {
                $searchTerm = $request->patient_name;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('patients.firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('patients.lastname', 'like', "%{$searchTerm}%");
                });
            }

            if ($request->filled('date')) {
                $query->whereDate('appointment_date', $request->date);
            }

            $appointments = $query->get()->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_id' => $appointment->patient_id, // Add this line
                    'patient_first_name' => $appointment->patient_first_name,
                    'patient_last_name' => $appointment->patient_last_name,
                    'phone' => $appointment->phone,
                    'patient_Date_Of_Birth' => $appointment->patient_Date_Of_Birth,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'doctor_name' => $appointment->doctor_name,
                    'specialization_id' => $appointment->specialization_id,
                    'doctor_id' => $request->doctor_id ?? $appointment->doctor_id,
                    'status' => $this->getStatusInfo($appointment->appointment_status)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $appointments
            ]);
        } catch (\Exception $e) {
            Log::error('Error in GetAllAppointments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getStatusInfo($status)
    {
        $statusMap = [
            0 => ['name' => 'Scheduled', 'color' => 'primary', 'icon' => 'fas fa-calendar'],
            1 => ['name' => 'Confirmed', 'color' => 'success', 'icon' => 'fas fa-check'],
            2 => ['name' => 'Canceled', 'color' => 'danger', 'icon' => 'fas fa-times'],
            3 => ['name' => 'Pending', 'color' => 'warning', 'icon' => 'fas fa-clock'],
            4 => ['name' => 'Done', 'color' => 'info', 'icon' => 'fas fa-check-double'],
            5 => ['name' => 'ONWORKING', 'color' => 'warning', 'icon' => 'fas fa-clock'],
        ];

        return [
            'value' => $status,
            'name' => $statusMap[$status]['name'] ?? 'Unknown',
            'color' => $statusMap[$status]['color'] ?? 'secondary',
            'icon' => $statusMap[$status]['icon'] ?? 'fas fa-question'
        ];
    }

    public function search(Request $request)
    {
        // Get query parameters
        $query = $request->input('query');
        $doctorId = $request->input('doctor_id');
        $appointmentDate = $request->input('appointment_date');
        $appointmentTime = $request->input('appointment_time');

        // Base query
        $appointments = Appointment::query()
            // Filter by patient name or date of birth
            ->when($query, function ($queryBuilder) use ($query) {
                $queryBuilder->whereHas('patient', function ($patientQuery) use ($query) {
                    $patientQuery->where('Firstname', 'like', "%{$query}%")
                        ->orWhere('Lastname', 'like', "%{$query}%")
                        ->orWhere('dateOfBirth', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                });
            })
            // Filter by doctor ID
            ->when($doctorId, function ($queryBuilder) use ($doctorId) {
                $queryBuilder->where('doctor_id', $doctorId);
            })
            // Filter by appointment date
            ->when($appointmentDate, function ($queryBuilder) use ($appointmentDate) {
                $queryBuilder->whereDate('appointment_date', $appointmentDate);
            })
            // Filter by appointment time
            ->when($appointmentTime, function ($queryBuilder) use ($appointmentTime) {
                $queryBuilder->whereTime('appointment_time', $appointmentTime);
            })
            // Eager load the patient relationship
            ->with('patient')
            // Paginate results
            ->paginate(10);

        return AppointmentResource::collection($appointments);
    }

    public function getDoctorWorkingHours($doctorId, $date)
    {
        // Access pre-loaded data
        $data = $this->availabilityData;

        // Use an internal cache for this specific request, then fallback to Laravel's Cache
        $requestCacheKey = "request_working_hours_{$doctorId}_{$date}";
        if (isset($this->bookedSlotsCache[$requestCacheKey])) { // Re-using bookedSlotsCache for simplicity, but a dedicated one might be better
            return $this->bookedSlotsCache[$requestCacheKey];
        }

        // Cache for 5 minutes at the application level
        $appCacheKey = "doctor_{$doctorId}_hours_{$date}";
        $workingHours = Cache::remember($appCacheKey, 5 * 60, function () use ($date, $data) {
            $dateObj = Carbon::parse($date);
            $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value; // e.g., 'monday'
            $dateString = $dateObj->format('Y-m-d');

            $doctor = $data->doctor;
            $activeSchedules = collect();

            // 1. Check for specific date limited exclusions (highest priority override)
            if (isset($data->limitedExcludedSchedules[$dateString])) {
                $limitedExclusion = $data->limitedExcludedSchedules[$dateString];
                // Ensure it applies to this specific day within its range
                if ($dateObj->between(Carbon::parse($limitedExclusion->start_date), Carbon::parse($limitedExclusion->end_date ?? $limitedExclusion->start_date))) {
                    $activeSchedules->push($limitedExclusion); // Use this as the schedule
                }
            }

            // If no limited exclusion overrides, check regular schedules
            if ($activeSchedules->isEmpty()) {
                // 2. Check for specific date schedules (second highest priority)
                if (isset($data->schedulesByDate[$dateString])) {
                    $activeSchedules = $activeSchedules->merge($data->schedulesByDate[$dateString]);
                }

                // 3. Check for recurring day of week schedules (lowest priority, used if no specific date schedules)
                if ($activeSchedules->isEmpty() && isset($data->schedulesByDay[$dayOfWeek])) {
                    $activeSchedules = $activeSchedules->merge($data->schedulesByDay[$dayOfWeek]);
                }
            }

            if ($activeSchedules->isEmpty()) {
                return []; // No active schedule found for this date
            }

            $calculatedWorkingHours = [];
            $now = Carbon::now();
            $isToday = $dateObj->isSameDay($now);
            $bufferTime = $now->copy()->addMinutes(5); // 5-minute buffer for today's appointments

            // Determine time slot calculation based on doctor's settings
            $timeSlotMinutes = (int)$doctor->time_slot;

            // If doctor has a fixed time slot defined, use it directly
            if ($timeSlotMinutes > 0) {
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $activeSchedules->firstWhere('shift_period', $shift);
                    if (!$schedule) {
                        continue;
                    }

                    $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);

                    $currentTime = clone $startTime;
                    while ($currentTime->lt($endTime)) {
                        if (!$isToday || $currentTime->greaterThan($bufferTime)) {
                            $calculatedWorkingHours[] = $currentTime->format('H:i');
                        }
                        $currentTime->addMinutes($timeSlotMinutes);
                    }
                }
            } else { // Calculate based on number of patients per shift
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $activeSchedules->firstWhere('shift_period', $shift);
                    if (!$schedule) {
                        continue;
                    }

                    $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);

                    $patientsForShift = (int)($schedule->number_of_patients_per_day ?? 0);

                    if ($patientsForShift <= 0) {
                        continue;
                    }

                    $totalDuration = $endTime->diffInMinutes($startTime);

                    if ($patientsForShift == 1) { // Special case for one patient per shift
                        if (!$isToday || $startTime->greaterThan($bufferTime)) {
                            $calculatedWorkingHours[] = $startTime->format('H:i');
                        }
                    } else {
                        // Calculate slot duration for multiple patients
                        $slotDuration = abs($totalDuration / ($patientsForShift - 1));

                        for ($i = 0; $i < $patientsForShift; $i++) {
                            $minutesToAdd = round($i * $slotDuration);
                            $slotTime = $startTime->copy()->addMinutes($minutesToAdd);

                            if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                                $calculatedWorkingHours[] = $slotTime->format('H:i');
                            }
                        }
                    }
                }
            }
            return array_unique($calculatedWorkingHours);
        });

        // Store in request-level cache
        $this->bookedSlotsCache[$requestCacheKey] = $workingHours;
        return $workingHours;
    }

    private function getBookedSlots($doctorId, $date)
    {
        $cacheKey = "booked_slots_{$doctorId}_{$date}";
        if (isset($this->bookedSlotsCache[$cacheKey])) {
            return $this->bookedSlotsCache[$cacheKey];
        }

        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', $this->excludedStatuses)
            ->pluck('appointment_time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->unique()
            ->toArray();

        $this->bookedSlotsCache[$cacheKey] = $bookedSlots;
        return $bookedSlots;
    }

    public function ForceAppointment(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'days' => 'nullable|integer',
            'date' => 'nullable|date_format:Y-m-d', // Accept date directly
        ]);

        $doctorId = $validated['doctor_id'];
        $days = (int)($validated['days'] ?? 0); // Ensure days is an integer
        $dateInput = $validated['date'] ?? null; // Get the date input if provided

        try {
            // Use the provided date if available, otherwise calculate it based on days
            $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : Carbon::now()->addDays($days)->format('Y-m-d');
            $dateObj = Carbon::parse($date);

            // Initialize availability data for this request
            $this->initAvailabilityData($doctorId);
            $doctorData = $this->availabilityData;
            $doctor = $doctorData->doctor;

            $morningSchedule = null;
            $afternoonSchedule = null;
            $forcedSchedule = null; // To hold either AppointmentForcer or a limited ExcludedDate

            // Check for limited excluded dates first, as they override regular schedules
            // Use pre-loaded limitedExcludedSchedules for efficiency
            $limitedExcludedDate = $doctorData->limitedExcludedSchedules[$date] ?? null;

            if ($limitedExcludedDate) {
                // If a limited exclusion exists, it defines the forced schedule
                $forcedSchedule = (object) [
                    'start_time' => $limitedExcludedDate->start_time,
                    'end_time' => $limitedExcludedDate->end_time,
                    'number_of_patients_per_day' => $limitedExcludedDate->number_of_patients_per_day,
                    'shift_period' => 'full_day' // Treat as a single block for calculation
                ];
            } else {
                // If no limited exclusion, get regular schedules from pre-loaded data
                $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;
                $schedules = collect($doctorData->schedulesByDate[$date] ?? [])
                            ->merge($doctorData->schedulesByDay[$dayOfWeek] ?? []);

                $morningSchedule = $schedules->firstWhere('shift_period', 'morning');
                $afternoonSchedule = $schedules->firstWhere('shift_period', 'afternoon');

                // If no regular schedules, check for general AppointmentForcer
                if (!$morningSchedule && !$afternoonSchedule) {
                    $forcedSchedule = AppointmentForcer::where('doctor_id', $doctorId)->first();
                    if ($forcedSchedule) {
                        // Ensure it has start and end times to be considered a valid forced schedule
                        if ($forcedSchedule->start_time === null || $forcedSchedule->end_time === null) {
                            $forcedSchedule = null; // Invalidate if incomplete
                        }
                    }
                }
            }

            $gapSlots = [];
            $additionalSlots = [];
            $finalTimeSlotMinutes = (int)$doctor->time_slot; // Start with doctor's default, then refine

            if ($forcedSchedule) {
                // Generate slots based on the forced schedule
                $generatedSlots = $this->generateForcedSlots($doctorId, $date, $forcedSchedule);
                $additionalSlots = $generatedSlots;

                // Recalculate timeSlotMinutes if derived from forced schedule patients
                if ($finalTimeSlotMinutes <= 0 && (isset($forcedSchedule->number_of_patients_per_day) && $forcedSchedule->number_of_patients_per_day > 0 || isset($forcedSchedule->number_of_patients) && $forcedSchedule->number_of_patients > 0)) {
                    $numPatients = $forcedSchedule->number_of_patients_per_day ?? $forcedSchedule->number_of_patients;
                    $forcedDuration = Carbon::parse($forcedSchedule->end_time)->diffInMinutes(Carbon::parse($forcedSchedule->start_time));
                    $finalTimeSlotMinutes = (int) ($forcedDuration / $numPatients);
                }
                // Ensure it's not zero or negative
                if ($finalTimeSlotMinutes <= 0) {
                    $finalTimeSlotMinutes = 30; // Fallback to a default if calculation yields zero or negative
                }
            } elseif ($morningSchedule || $afternoonSchedule) {
                // Handle regular schedules (morning and/or afternoon)
                if ($morningSchedule && $afternoonSchedule) {
                    $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
                    $afternoonStartTime = Carbon::parse($date . ' ' . $afternoonSchedule->start_time);

                    $currentTime = clone $morningEndTime;
                    // Calculate `finalTimeSlotMinutes` based on the actual schedule for gap calculation
                    if ($finalTimeSlotMinutes <= 0) { // If doctor has no fixed time slot
                        $finalTimeSlotMinutes = $this->calculateTimeSlotMinutes($doctor, $date);
                        if ($finalTimeSlotMinutes <= 0) $finalTimeSlotMinutes = 30; // Fallback
                    }

                    while ($currentTime < $afternoonStartTime) {
                        $gapSlots[] = $currentTime->format('H:i');
                        $currentTime->addMinutes($finalTimeSlotMinutes);
                    }
                }
                // For regular schedules, `additional_slots` should be empty because slots are generated
                // within `getDoctorWorkingHours` and returned as `available_slots`
                $additionalSlots = []; // No additional slots outside defined shifts for regular schedules
            } else {
                // If no regular schedule and no forced schedule found, then the doctor isn't scheduled.
                return response()->json([
                    'gap_slots' => [],
                    'additional_slots' => [],
                    'next_available_date' => null, // No forced or regular schedule, so no slots
                    'time_slot_minutes' => $finalTimeSlotMinutes,
                    'message' => 'No regular or forced schedule found for this date. Doctor might not be working.'
                ], 200);
            }

            return response()->json([
                'gap_slots' => $gapSlots,
                'additional_slots' => $additionalSlots,
                'next_available_date' => $date,
                'time_slot_minutes' => $finalTimeSlotMinutes,
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating slots: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'error' => 'Error calculating appointment slots: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Fetch doctor details.
     */
    private function fetchDoctorDetails($doctorId)
    {
        // This is now redundant as doctor details are pre-loaded in initAvailabilityData
        // However, if called outside of a request where initAvailabilityData is run, it could be useful.
        // For consistency and to leverage initAvailabilityData, it's better to get doctor data from this->availabilityData->doctor
        if ($this->availabilityData && $this->availabilityData->doctor->id == $doctorId) {
            return $this->availabilityData->doctor;
        }
        return Doctor::find($doctorId, ['time_slot']);
    }

    /**
     * Calculate time slot duration dynamically if not set.
     */
    private function calculateTimeSlotMinutes($doctor, $date)
    {
        $timeSlotMinutes = is_numeric($doctor->time_slot) ? (int) $doctor->time_slot : 0;

        if ($timeSlotMinutes <= 0) {
            $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;

            // Fetch schedules from pre-loaded data
            $schedules = collect($this->availabilityData->schedulesByDate[Carbon::parse($date)->format('Y-m-d')] ?? [])
                        ->merge($this->availabilityData->schedulesByDay[$dayOfWeek] ?? []);

            // Initialize patient counts
            $morningPatients = 0;
            $afternoonPatients = 0;

            // Loop through the schedules to correctly assign values
            foreach ($schedules as $schedule) {
                if ($schedule->shift_period === 'morning' && $schedule->number_of_patients_per_day !== null) {
                    $morningPatients += $schedule->number_of_patients_per_day;
                }
                if ($schedule->shift_period === 'afternoon' && $schedule->number_of_patients_per_day !== null) {
                    $afternoonPatients += $schedule->number_of_patients_per_day;
                }
            }

            // Total number of patients
            $totalPatients = $morningPatients + $afternoonPatients;

            $totalAvailableTime = $this->calculateTotalAvailableTime($doctor->id, $date);

            if ($totalPatients > 0) {
                $timeSlotMinutes = (int)(abs($totalAvailableTime) / $totalPatients);
            } else {
                $timeSlotMinutes = 30; // Default to 30 minutes
            }
        }
        // Ensure timeSlotMinutes is never zero or negative.
        if ($timeSlotMinutes <= 0) {
            $timeSlotMinutes = 30;
        }
        return $timeSlotMinutes;
    }


    /**
     * Calculate total available time for the day.
     */
    private function calculateTotalAvailableTime($doctorId, $date)
    {
        $dateObj = Carbon::parse($date);
        $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;

        // Fetch schedules from pre-loaded data (or fallback to DB if not pre-loaded for some reason)
        $schedules = collect($this->availabilityData->schedulesByDate[$dateObj->format('Y-m-d')] ?? [])
                    ->merge($this->availabilityData->schedulesByDay[$dayOfWeek] ?? []);

        $totalAvailableTime = 0;

        foreach ($schedules as $schedule) {
            $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
            $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
            $totalAvailableTime += $endTime->diffInMinutes($startTime);
        }

        return $totalAvailableTime;
    }

    /**
     * Fetch schedules for the given doctor and date.
     * This method is now simplified as schedule data should ideally come from initAvailabilityData.
     */
    private function fetchSchedules($doctorId, $date)
    {
        $dateObj = Carbon::parse($date);
        $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;

        // Prioritize specific date schedules, then recurring ones
        $specificDateSchedules = $this->availabilityData->schedulesByDate[$dateObj->format('Y-m-d')] ?? null;
        if ($specificDateSchedules) {
            return collect($specificDateSchedules);
        }

        return $this->availabilityData->schedulesByDay[$dayOfWeek] ?? collect();
    }


    /**
     * Generate slots based on a forced schedule (AppointmentForcer or limited ExcludedDate).
     *
     * @param int $doctorId
     * @param string $date
     * @param object $forcedSchedule Can be an AppointmentForcer model or a limited ExcludedDate model
     * @return array
     */
    private function generateForcedSlots($doctorId, $date, $forcedSchedule)
    {
        $startTime = Carbon::parse($date . ' ' . ($forcedSchedule->start_time ?? '08:00'));
        $endTime = Carbon::parse($date . ' ' . ($forcedSchedule->end_time ?? '17:00'));
        $slots = [];

        // Get doctor's configured fixed time slot
        $doctor = $this->availabilityData->doctor;
        $timeSlotMinutes = (int) $doctor->time_slot;

        // If the doctor has no fixed time_slot, calculate it based on the forced schedule's patients
        if ($timeSlotMinutes <= 0) {
            $numberOfPatients = (int) ($forcedSchedule->number_of_patients_per_day ?? $forcedSchedule->number_of_patients ?? 0);
            if ($numberOfPatients > 0) {
                $totalDuration = $endTime->diffInMinutes($startTime);
                $timeSlotMinutes = (int) ($totalDuration / $numberOfPatients);
            } else {
                $timeSlotMinutes = 30; // Fallback default if no patient count or invalid
            }
        }
        // Ensure timeSlotMinutes is never zero or negative.
        if ($timeSlotMinutes <= 0) {
            $timeSlotMinutes = 30;
        }

        $bookedSlots = $this->getBookedSlots($doctorId, $date);

        $currentTime = clone $startTime;
        while ($currentTime->lte($endTime)) { // Use lte to include the last slot if it aligns perfectly
            $slotTime = $currentTime->format('H:i');

            // Only add slot if it's within the defined range and not booked
            if ($currentTime->lessThanOrEqualTo($endTime) && !in_array($slotTime, $bookedSlots)) {
                $slots[] = $slotTime;
            }

            $currentTime->addMinutes($timeSlotMinutes);
        }
        return array_unique($slots);
    }


    public function printTicket(Request $request)
    {
        try {
            $data = $request->validate([
                'patient_first_name' => 'required|string|max:255',
                'patient_last_name' => 'required|string|max:255',
                'doctor_name' => 'nullable|string|max:255',
                'doctor_id' => 'nullable|integer',
                'appointment_date' => 'required',
                'appointment_time' => 'required|date_format:H:i',
                'description' => 'nullable|string|max:1000'
            ]);
            if ($data['doctor_id']) {
                $doctor = Doctor::with('user')->find($data['doctor_id']);
                if ($doctor) {
                    $data['doctor_name'] = $doctor->user->name;
                }
            }
            $data['user_name'] = Auth::user()->name;

            // Set paper size for thermal printer (typically 80mm width)
            $customPaper = array(0, 0, 226.77, 141.73); // 80mm x 50mm in points (1 inch = 72 points)
            // Set paper size for 8 cm thermal printer (approximately 226.77 points width)
            $customPaper = array(0, 0, 226.77, 283.46); // 8cm width x 10cm height in points

            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'XB Zar',
                'chroot' => storage_path('app/public'),
                'paperSize' => $customPaper,
                'margin-top' => 2,
                'margin-right' => 2,
                'margin-bottom' => 2,
                'margin-left' => 2,
                'dpi' => 203 // Standard DPI for thermal printers
            ])->loadView('tickets.appointment', $data);

            // Force the PDF to be black and white
            $pdf->setOption('grayscale', true);

            return $pdf->download('ticket.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function printConfirmationTicket(Request $request)
    {
        try {
            $data = $request->validate([
                'patient_first_name' => 'required|string|max:255',
                'patient_last_name' => 'required|string|max:255',
                'specialization_id' => 'nullable|max:255',
                'doctor_name' => 'required|string|max:255',
                'appointment_date' => 'required',
                'appointment_time' => 'required|date_format:H:i',
                'description' => 'nullable|string|max:1000'
            ]);

            $data['specialization_name'] = \App\Models\Specialization::find($data['specialization_id'])->name;
            $data['user_name'] = Auth::user()->name;

            // Set paper size for thermal printer (80mm width)

            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'XB Zar',
                'chroot' => storage_path('app/public'),
                'margin-top' => 2,
                'margin-right' => 2,
                'margin-bottom' => 2,
                'margin-left' => 2,
            ])->loadView('tickets.Confirmation', $data);

            // Force black and white output
            $pdf->setOption('grayscale', true);

            return $pdf->download('ticket.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle schedules for the given date.
     * This method is now simplified as it no longer generates 'additional slots'
     * after the regular shifts, as that logic is more appropriate for `generateForcedSlots`.
     * It primarily calculates 'gap slots' if both morning and afternoon schedules exist.
     */
    private function handleSchedules($morningSchedule, $afternoonSchedule, $date, $timeSlotMinutes)
    {
        $gapSlots = [];
        // Removed $additionalSlots as it's now handled by generateForcedSlots if applicable

        if ($morningSchedule && $afternoonSchedule) {
            $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
            $afternoonStartTime = Carbon::parse($date . ' ' . $afternoonSchedule->start_time);

            // Ensure timeSlotMinutes is valid before using it
            if ($timeSlotMinutes <= 0) {
                // Fallback to recalculate if it became invalid, though it should be set by ForceAppointment
                $timeSlotMinutes = $this->calculateTimeSlotMinutes($this->availabilityData->doctor, $date);
                if ($timeSlotMinutes <= 0) $timeSlotMinutes = 30; // Final fallback
            }

            $currentTime = clone $morningEndTime;
            while ($currentTime < $afternoonStartTime) {
                $gapSlots[] = $currentTime->format('H:i');
                $currentTime->addMinutes($timeSlotMinutes);
            }
        }
        return [$gapSlots, []]; // Return empty array for additional slots
    }

    /**
     * This method is now deprecated and its logic is integrated directly into `generateForcedSlots`.
     */
    // private function generateAdditionalSlots($endTime, $timeSlotMinutes) { ... }

    /**
     * Retrieve a specific appointment for a doctor.
     *
     * @param  int  $doctorId
     * @param  int  $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointment($doctorId, $appointmentId)
    {
        $appointment = Appointment::where('doctor_id', $doctorId)
            ->where('id', $appointmentId)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function checkAvailability(Request $request)
    {
        
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
                'date' => 'nullable|date_format:Y-m-d',
                'days' => 'nullable|integer|min:0', // Changed min to 0 as it can be current day
                'range' => 'nullable|integer|min:0',
                'include_slots' => 'nullable|in:true,false,1,0'
            ]);

            $doctorId = $validated['doctor_id'];

            // Initialize availability data ONCE for this request
            $this->initAvailabilityData($doctorId);

            // Determine the start date for the search
            if (isset($validated['date'])) {
                $startDate = Carbon::parse($validated['date']);
            } else {
                $days = isset($validated['days']) ? (int) $validated['days'] : 0;
                $startDate = Carbon::now()->addDays($days);
            }

            // Get range if provided, default to 0
            $range = isset($validated['range']) ? (int) $validated['range'] : 0;

            // Find next available appointment based on range
            if ($range > 0) {
                $nextAvailableDate = $this->findNextAvailableAppointmentWithinRange($startDate, $range);
            } else {
                $nextAvailableDate = $this->findNextAvailableAppointment($startDate);
            }

            // Build the response
            $response = [];
            if (!$nextAvailableDate) {
                $response = [
                    'success' => true,
                    'is_available' => false,
                    'next_available_date' => null,
                    'available_slots' => [],
                    'period' => null
                ];
            } else {
                // Calculate period difference from current date
                $daysDifference = $nextAvailableDate->diffInDays(Carbon::now());
                $period = $this->calculatePeriod($daysDifference);

                $response = [
                    'success' => true,
                    'is_available' => true,
                    'current_date' => Carbon::now()->format('Y-m-d'),
                    'next_available_date' => $nextAvailableDate->format('Y-m-d'),
                    'period' => $period
                ];

                // Convert string boolean to actual boolean
                $includeSlots = isset($validated['include_slots']) &&
                    in_array($validated['include_slots'], ['true', '1'], true);

                if ($includeSlots) {
                    $workingHours = $this->getDoctorWorkingHours($doctorId, $nextAvailableDate->format('Y-m-d'));
                    $bookedSlots = $this->getBookedSlots($doctorId, $nextAvailableDate->format('Y-m-d'));
                    // Find available slots by subtracting booked slots from working hours
                    $availableSlots = array_diff($workingHours, $bookedSlots);

                    // Add slots information to response
                    $response['available_slots'] = array_values($availableSlots);
                }
            }

            return response()->json($response);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error in checkAvailability', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            // Return a user-friendly error response
            return response()->json([
                'message' => 'An error occurred while checking availability.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllCanceledAppointments(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
            ]);

            $doctorId = $validated['doctor_id'];

            // Initialize availability data for this request
            $this->initAvailabilityData($doctorId);

            // Fetch future canceled appointments
            $now = Carbon::now();
            $canceledAppointments = Appointment::where('doctor_id', $doctorId)
                ->where('status', AppointmentSatatusEnum::CANCELED->value)
                ->where(function ($query) use ($now) {
                    $query->where('appointment_date', '>', $now->format('Y-m-d'))
                        ->orWhere(function ($query) use ($now) {
                            $query->where('appointment_date', '=', $now->format('Y-m-d'))
                                ->where('appointment_time', '>', $now->format('H:i'));
                        });
                })
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get();

            $availableCanceledSlots = [];
            foreach ($canceledAppointments->groupBy('appointment_date') as $dateString => $appointmentsOnDate) {
                $currentDate = Carbon::parse($dateString);

                // Use the new centralized availability check
                if ($this->isDateTrulyAvailable($currentDate)) {
                    $availableTimes = [];
                    $workingHours = $this->getDoctorWorkingHours($doctorId, $dateString);
                    $bookedSlots = $this->getBookedSlots($doctorId, $dateString);

                    foreach ($appointmentsOnDate as $appointment) {
                        $time = Carbon::parse($appointment->appointment_time)->format('H:i');
                        // Ensure the slot is within working hours and not re-booked
                        if (in_array($time, $workingHours) && !in_array($time, $bookedSlots)) {
                            $availableTimes[] = $time;
                        }
                    }
                    if (!empty($availableTimes)) {
                        $availableCanceledSlots[] = [
                            'date' => $dateString,
                            'available_times' => $availableTimes,
                            'appointments_details' => AppointmentResource::collection($appointmentsOnDate->whereIn('appointment_time', $availableTimes))
                        ];
                    }
                }
            }

            return response()->json([
                'current_date' => $now->format('Y-m-d'),
                'available_canceled_appointments' => $availableCanceledSlots
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAllCanceledAppointments', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'An error occurred while fetching canceled appointments.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Calculate period in days/months/years.
     *
     * @param int $days
     * @return string
     */
    private function calculatePeriod($days)
    {
        // Ensure that $days is an integer before processing
        $days = (int) $days;

        if ($days >= 365) {
            $years = floor($days / 365);
            $remainingDays = $days % 365;
            return $years . ' year(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
        }

        if ($days >= 30) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            return $months . ' month(s)' . ($remainingDays ? ' and ' . $remainingDays . ' day(s)' : '');
        }

        return $days . ' day(s)';
    }

    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'addToWaitlist' => 'nullable|boolean',
            'prestation_id' => 'nullable|integer|exists:prestations,id',
            'prestations' => 'nullable|array',
            'prestations.*' => 'nullable|integer|exists:prestations,id',
        ]);

        // Initialize availability data for the given doctor
        $this->initAvailabilityData($validated['doctor_id']);

        // Check if the slot is already booked using the model method
        if (
            !Appointment::isSlotAvailable(
                $validated['doctor_id'],
                $validated['appointment_date'],
                $validated['appointment_time'],
                $this->excludedStatuses
            )
        ) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }

        // Create the appointment
        $appointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'add_to_waitlist' => $validated['addToWaitlist'] ?? false,
            'notes' => $validated['description'] ?? null,
            'status' => 0,
            'created_by' => Auth::id(),
        ]);

        // Handle prestation storage with improved validation and error handling
        try {
            $selectedPrestations = [];

            // Collect prestation IDs from both single and array inputs
            if (!empty($validated['prestation_id'])) {
                $selectedPrestations[] = (int)$validated['prestation_id'];
            }

            if (!empty($validated['prestations']) && is_array($validated['prestations'])) {
                // Filter out null/empty values and ensure integers
                $arrayPrestations = array_filter($validated['prestations'], function($prestationId) {
                    return !is_null($prestationId) && is_numeric($prestationId);
                });
                $selectedPrestations = array_merge($selectedPrestations, array_map('intval', $arrayPrestations));
            }

            // Remove duplicates and filter out invalid IDs
            $selectedPrestations = array_values(array_unique(array_filter($selectedPrestations, function($id) {
                return $id > 0;
            })));

            // Store prestations if any are selected
            if (!empty($selectedPrestations)) {
                $prestationRecords = [];
                foreach ($selectedPrestations as $prestationId) {
                    $prestationRecords[] = [
                        'appointment_id' => $appointment->id,
                        'prestation_id' => $prestationId,
                        'description' => '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Use batch insert for better performance
                AppointmentPrestation::insert($prestationRecords);

                Log::info('Prestations stored successfully', [
                    'appointment_id' => $appointment->id,
                    'prestation_ids' => $selectedPrestations
                ]);
            } else {
                Log::info('No prestations selected for appointment', ['appointment_id' => $appointment->id]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to save appointment prestations (store)', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
                'payload' => $validated,
                'selected_prestations' => $selectedPrestations ?? []
            ]);

            // Consider whether to rollback the appointment creation
            // For now, we'll keep the appointment but log the error
            // You might want to delete the appointment if prestation storage is critical
        }

        // If adding to the waitlist, create a record
        if ($validated['addToWaitlist'] ?? false) {
            try {
                $doctor = Doctor::find($validated['doctor_id']);
                WaitList::create([
                    'doctor_id' => $validated['doctor_id'],
                    'patient_id' => $validated['patient_id'],
                    'is_Daily' => false,
                    'specialization_id' => $doctor->specialization_id ?? null,
                    'importance' => null,
                    'appointmentId' => $appointment->id ?? null,
                    'notes' => $validated['description'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to create waitlist entry', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $appointment->id
                ]);
            }
        }

        return new AppointmentResource($appointment);
    }

    public function nextAppointment(Request $request, $appointmentId)
    {
        // Find the existing appointment and mark it as done
        $existingAppointment = Appointment::findOrFail($appointmentId);
        $existingAppointment->update(['status' => 4]); // Mark as DONE

        // Validate the request for the new appointment
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'addToWaitlist' => 'nullable|boolean',
        ]);

        // Initialize availability data for the new appointment's doctor
        $this->initAvailabilityData($validated['doctor_id']);

        // Check if the new slot is already booked
        $excludedStatuses = [AppointmentSatatusEnum::CANCELED->value];

        $isSlotBooked = Appointment::where('doctor_id', $validated['doctor_id'])
            ->whereDate('appointment_date', $validated['appointment_date'])
            ->where('appointment_time', $validated['appointment_time'])
            ->whereNotIn('status', $excludedStatuses)
            ->exists();

        if ($isSlotBooked) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }

        // Create the new appointment
        $newAppointment = Appointment::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'add_to_waitlist' => $validated['addToWaitlist'] ?? false,
            'notes' => $validated['description'] ?? null,

            'status' => 0,  // Default to pending
            'created_by' => Auth::id(), // Assuming created_by is the user ID
        ]);

        // Handle waitlist addition if necessary
        if ($validated['addToWaitlist'] ?? false) {
            $doctor = Doctor::find($validated['doctor_id']);
            $specialization_id = $doctor->specialization_id;

            WaitList::create([
                'doctor_id' => $validated['doctor_id'],
                'patient_id' => $validated['patient_id'],
                'is_Daily' => false,
                'specialization_id' => $specialization_id,
                'importance' => null,
                'appointmentId' => $newAppointment->id ?? null,
                'notes' => $validated['description'] ?? null,
                'created_by' => Auth::id(),
            ]);
        }

        return new AppointmentResource($newAppointment);
    }

    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return new AppointmentResource($appointment);
    }

    public function AvailableAppointments(Request $request)
    {
        try {
            $validated = $request->validate([
                'doctor_id' => 'required|exists:doctors,id',
            ]);

            $doctorId = $validated['doctor_id'];
            $now = Carbon::now();

            // Initialize availability data ONCE for all subsequent checks in this request
            $this->initAvailabilityData($doctorId);

            // --- Part 1: Canceled Appointments that are now available ---
            $canceledAppointments = Appointment::where('doctor_id', $doctorId)
                ->where('status', AppointmentSatatusEnum::CANCELED->value)
                ->where(function ($query) use ($now) {
                    $query->where('appointment_date', '>', $now->format('Y-m-d'))
                        ->orWhere(function ($query) use ($now) {
                            $query->where('appointment_date', '=', $now->format('Y-m-d'))
                                ->where('appointment_time', '>', $now->format('H:i'));
                        });
                })
                ->with(['patient:id,Lastname,Firstname', 'doctor:id,user_id', 'doctor.user:id,name'])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get();

            $canceledAppointmentsResult = [];
            $groupedCanceledAppointments = $canceledAppointments->groupBy('appointment_date');

            foreach ($groupedCanceledAppointments as $dateString => $appointmentsOnDate) {
                $searchDate = Carbon::parse($dateString);

                // Check if the DATE itself is generally available (not completely excluded, month available, has schedule)
                if ($this->isDateTrulyAvailable($searchDate)) {
                    $availableTimesOnDate = [];
                    $workingHours = $this->getDoctorWorkingHours($doctorId, $dateString);
                    $bookedSlots = $this->getBookedSlots($doctorId, $dateString); // Get all booked for this date

                    foreach ($appointmentsOnDate as $canceledAppt) {
                        $time = Carbon::parse($canceledAppt->appointment_time)->format('H:i');

                        // Check if this specific time slot is within working hours AND not currently booked by another non-canceled appointment
                        if (in_array($time, $workingHours) && !in_array($time, $bookedSlots)) {
                            $availableTimesOnDate[] = $time;
                        }
                    }

                    if (!empty($availableTimesOnDate)) {
                        $canceledAppointmentsResult[] = [
                            'date' => $dateString,
                            'available_times' => $availableTimesOnDate,
                            'appointments_details' => AppointmentResource::collection($appointmentsOnDate->whereIn('appointment_time', $availableTimesOnDate))
                        ];
                    }
                }
            }

            // --- Part 2: Next Normal Available Appointment ---
            $startDateForNormalSearch = Carbon::now();
            $nextAvailableDate = $this->findNextAvailableAppointment($startDateForNormalSearch);

            $normalAppointmentResult = null;
            if ($nextAvailableDate) {
                $daysDifference = $nextAvailableDate->diffInDays($now);
                $workingHours = $this->getDoctorWorkingHours($doctorId, $nextAvailableDate->format('Y-m-d'));
                $bookedSlots = $this->getBookedSlots($doctorId, $nextAvailableDate->format('Y-m-d'));
                $availableSlots = array_values(array_diff($workingHours, $bookedSlots));

                // Only return if there are actual slots available for this date
                if (!empty($availableSlots)) {
                    $normalAppointmentResult = [
                        'date' => $nextAvailableDate->format('Y-m-d'),
                        'available_times' => $availableSlots,
                        'period' => $this->calculatePeriod($daysDifference)
                    ];
                }
            }

            return response()->json([
                'canceled_appointments' => $canceledAppointmentsResult,
                'normal_appointments' => $normalAppointmentResult
            ]);
        } catch (\Exception $e) {
            Log::error('Error in AvailableAppointments', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'An error occurred while checking availability.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeAppointmentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:' . implode(',', array_column(AppointmentSatatusEnum::cases(), 'value')),
            'reason' => 'nullable|string'
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->status = $validated['status'];

        // Ensure 'reason' is never NULL
        if ($validated['status'] == AppointmentSatatusEnum::CANCELED->value) {
            $appointment->reason = $validated['reason'] ?? '--'; // Use provided reason or default '--'
            $appointment->canceled_by = Auth::id();
        } else {
            $appointment->reason = '--'; // Default when status is not 2
        }

        $appointment->save();

        return response()->json([
            'message' => 'Appointment status updated successfully.',
            'appointment' => new AppointmentResource($appointment),
        ]);
    }

    public function destroy($appointmentid)
    {
        $appointment = Appointment::findOrFail($appointmentid);
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        // Find the appointment by ID
        $appointment = Appointment::findOrFail($id);
        $patient = Patient::findOrFail($appointment->patient_id); // Find the associated patient

        // Define base validation rules
        $rules = [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'doctor_id' => 'sometimes|required|exists:doctors,id',
            'appointment_date' => 'sometimes|required|date_format:Y-m-d',
            'appointment_time' => 'sometimes|required|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'addToWaitlist' => 'nullable|boolean',
            'importance' => 'nullable|integer',
            'prestation_id' => 'nullable|integer|exists:prestations,id',
            'prestations' => 'nullable|array',
            'prestations.*' => 'nullable|integer|exists:prestations,id',
        ];

        // Apply conditional validation for 'importance' if 'addToWaitlist' is true
        // Important: Ensure `Illuminate\Validation\Rule` is imported at the top of the file.
        $rules['importance'] = [
            'nullable',
            'integer',
            Rule::requiredIf($request->boolean('addToWaitlist')),
        ];

        // Validate the request data
        $validated = $request->validate($rules);

        // Get the current values or new validated values
        $doctorId = $validated['doctor_id'] ?? $appointment->doctor_id;
        $appointmentDate = $validated['appointment_date'] ?? $appointment->appointment_date;
        $appointmentTime = $validated['appointment_time'] ?? $appointment->appointment_time;

        // Initialize availability data for the doctor (if not already done)
        $this->initAvailabilityData($doctorId);

        // Check if the time slot is already booked, excluding the current appointment's ID
        if (!Appointment::isSlotAvailableForUpdate($doctorId, $appointmentDate, $appointmentTime, $this->excludedStatuses, $appointment->id)) {
            return response()->json([
                'message' => 'This time slot is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
            ], 422);
        }

        // Update the patient details
        $patient->update([
            'Firstname' => $validated['first_name'] ?? $patient->Firstname,
            'Lastname' => $validated['last_name'] ?? $patient->Lastname,
            'phone' => $validated['phone'] ?? $patient->phone,
            // Add DateOfBirth update if desired, based on the second snippet:
            // 'DateOfBirth' => $validated['dateOfBirth'] ?? $patient->DateOfBirth,
        ]);

        // Update the appointment details
        $appointment->update([
            'doctor_id' => $doctorId,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'status' => 0, // Reset status to scheduled/pending or based on your logic
            'updated_by' => Auth::id(),
            'add_to_waitlist' => $validated['addToWaitlist'] ?? $appointment->add_to_waitlist,
            'notes' => $validated['description'] ?? $appointment->notes, // Use 'notes' for description consistency
        ]);

        // Handle waitlist logic using the model method
        if (isset($validated['addToWaitlist'])) { // Check if the field was even sent
            $doctor = Doctor::find($doctorId);
            WaitList::updateOrDeleteWaitlist(
                $doctorId,
                $patient->id,
                $validated['addToWaitlist'], // This is a boolean, true means add/update, false means delete
                $doctor->specialization_id ?? null,
                $validated['importance'] ?? null,
                $validated['description'] ?? null // Use 'description' from original request name
            );
        }

        // After appointment update: sync prestations if provided
        try {
            // normalize incoming prestation ids
            $incomingPrestations = [];
            if ($request->filled('prestation_id')) {
                $incomingPrestations[] = (int)$request->input('prestation_id');
            }
            if ($request->has('prestations') && is_array($request->input('prestations'))) {
                $incomingPrestations = array_merge($incomingPrestations, $request->input('prestations'));
            }
            $incomingPrestations = array_values(array_unique(array_filter($incomingPrestations)));

            if (!empty($incomingPrestations)) {
                // existing prestation ids for this appointment
                $existing = AppointmentPrestation::where('appointment_id', $appointment->id)->pluck('prestation_id')->map(fn($v) => (int)$v)->toArray();

                // delete ones removed
                $toDelete = array_diff($existing, $incomingPrestations);
                if (!empty($toDelete)) {
                    AppointmentPrestation::where('appointment_id', $appointment->id)->whereIn('prestation_id', $toDelete)->delete();
                }

                // create new ones
                $toAdd = array_diff($incomingPrestations, $existing);
                foreach ($toAdd as $pid) {
                    AppointmentPrestation::create([
                        'appointment_id' => $appointment->id,
                        'prestation_id' => (int)$pid,
                        'description' => null
                    ]);
                }
            } else {
                // if incomingPrestations is empty but request explicitly sent empty array, remove all prestations
                if ($request->has('prestations') && is_array($request->input('prestations')) && count($request->input('prestations')) === 0) {
                    AppointmentPrestation::where('appointment_id', $appointment->id)->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to sync appointment prestations (update)', ['error' => $e->getMessage(), 'appointment_id' => $appointment->id, 'request' => $request->all()]);
        }

        return new AppointmentResource($appointment);
    }
}