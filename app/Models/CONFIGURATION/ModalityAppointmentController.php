<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\AppointmentSatatusEnum; // Assuming you'll create this enum
use App\Http\Controllers\Controller;
use App\DayOfWeekEnum; // This enum can likely be reused
use App\Http\Resources\CONFIGURATION\ModalityAppointmentResource; // Assuming you'll create this resource
use App\Models\CONFIGURATION\Modality;


//import ModalityAppointmentsImport

use App\Models\CONFIGURATION\MoalityAppointments; // Corrected model name
use App\Models\CONFIGURATION\ModalityAvailableMonth; // Assuming this model exists
use App\Models\CONFIGURATION\ModalitySchedule; // Assuming this model exists
use App\Models\CONFIGURATION\AppointmentModalityForce; // Assuming you'll create this model
// use App\Models\CONFIGURATION\ModalityExcludedDate; // Removed this import
use App\Imports\CONFIGURATION\ModalityAppointmentsImport; // Assuming this model exists
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModalityAppointmentController extends Controller
{
    /**
     * Statuses to exclude when checking availability
     */
    private $excludedStatuses = [
        2, // CANCELED
        // Add other statuses you want to exclude
    ];

    private $statusLabels = [
        0 => 'Scheduled',
        1 => 'Confirmed',
        2 => 'Canceled',
        3 => 'Pending',
        4 => 'Done',
        5 => 'OnWorking'
    ];


    public function index(Request $request, $modalityId)
    {
        try {
            $query = MoalityAppointments::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'modality:id,name,specialization_id,slot_type,time_slot_duration',
                    'creator:id,name',
                    'updater:id,name',
                    'canceller:id,name',
                ])
                ->whereHas('modality', function ($query) {
                    $query->whereNull('deleted_at'); // Assuming modalities can be soft-deleted
                })
                ->where('modality_id', $modalityId)
                ->whereNull('deleted_at'); // Assuming modality appointments can be soft-deleted

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date')) {
                $query->whereDate('appointment_date', $request->date);
            }

            if ($request->filter === 'today') {
                $query->whereDate('appointment_date', Carbon::now()->toDateString());
            }

            $query->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            $modalityAppointments = $query->paginate(30);

            return response()->json([
                'success' => true,
                'data' => ModalityAppointmentResource::collection($modalityAppointments),
                'meta' => [
                    'current_page' => $modalityAppointments->currentPage(),
                    'per_page' => $modalityAppointments->perPage(),
                    'total' => $modalityAppointments->total(),
                    'last_page' => $modalityAppointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch modality appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultationAppointment(Request $request, $modalityId)
    {
        try {
            $query = MoalityAppointments::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'modality:id,name,specialization_id',
                    'creator:id,name',
                    'updater:id,name',
                    'canceller:id,name',
                ])
                ->whereHas('modality', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->where('modality_id', $modalityId)
                ->whereNull('deleted_at');

            // Handle multiple statuses or single status
            if ($request->has('statuses') && is_array($request->statuses)) {
                $statuses = $request->statuses;

                $query->where(function ($q) use ($statuses) {
                    foreach ($statuses as $status) {
                        $q->orWhere(function ($subQuery) use ($status) {
                            $subQuery->where('status', $status);

                            // ONWORKING (5) and DONE (4) appointments should show regardless of date
                            if ($status != AppointmentSatatusEnum::ONWORKING->value && $status != AppointmentSatatusEnum::DONE->value) {
                                // Filter for today's appointments
                                $subQuery->where('appointment_date', Carbon::now()->toDateString());
                            }

                            // For DONE status, show only appointments completed today
                            if ($status == AppointmentSatatusEnum::DONE->value) {
                                $subQuery->whereDate('updated_at', Carbon::now()->toDateString());
                            }
                        });
                    }
                });
            } else {
                // Handle single status (existing logic with modifications)
                if ($request->status != AppointmentSatatusEnum::CANCELED->value) {
                    // Apply 'today' filter only if status is not ONWORKING (5) and not DONE (4)
                    if ($request->status != AppointmentSatatusEnum::ONWORKING->value && $request->status != AppointmentSatatusEnum::DONE->value) {
                        $query->where('appointment_date', Carbon::now()->toDateString());
                    }

                    // For DONE status, show only appointments completed today
                    if ($request->status == AppointmentSatatusEnum::DONE->value) {
                        $query->whereDate('updated_at', Carbon::now()->toDateString());
                    }
                }
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('date')) {
                    $query->whereDate('appointment_date', $request->date);
                }
                if ($request->filter === 'today') {
                    $query->whereDate('appointment_date', Carbon::now()->toDateString());
                }
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
                    ->orderBy('appointment_time', 'desc');
            }

            $modalityAppointments = $query->paginate(30);

            return response()->json([
                'success' => true,
                'data' => ModalityAppointmentResource::collection($modalityAppointments),
                'meta' => [
                    'current_page' => $modalityAppointments->currentPage(),
                    'per_page' => $modalityAppointments->perPage(),
                    'total' => $modalityAppointments->total(),
                    'last_page' => $modalityAppointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch modality consultation appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateModalityAppointmentsPdf(Request $request)
    {
        try {
            // Start building the query
            $query = MoalityAppointments::query()
                ->with([
                    'patient:id,Lastname,Firstname,phone,dateOfBirth',
                    'modality:id,name,specialization_id',
                    'modality.specialization:id,name' // Assuming modality has a specialization relationship
                ])
                ->whereHas('modality', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            // Apply all possible filters
            $this->applyModalityFilters($query, $request);

            // Fetch the filtered modality appointments
            $modalityAppointments = $query->get();

            // Modality does not have a user table like Doctor, so includeTime logic needs adjustment
            // If Modality has a property like `include_time` on the model directly, use that.
            // For now, I'll set it to false as it's not present in your Modality model fields.
            $includeTime = false; // Or fetch from modality settings if applicable

            // Transform modality appointments to include status labels
            $transformedModalityAppointments = $modalityAppointments->map(function ($appointment) {
                $appointment->status_label = $this->statusLabels[$appointment->status] ?? 'Unknown';
                return $appointment;
            });

            // Get the filter summary for the PDF header
            $filterSummary = $this->getModalityFilterSummary($request);

            // Generate PDF with filter summary
            $pdf = PDF::loadView('pdf.modality_appointments', [ // You'll need to create this Blade view
                'modalityAppointments' => $transformedModalityAppointments,
                'filterSummary' => $filterSummary,
                'includeTime' => $includeTime,
            ]);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'landscape');

            $modalityName = preg_replace('/[^A-Za-z0-9\-]/', '_', $request->modalityName ?? 'all_modalities'); // Clean filename
            $date = Carbon::now()->format('Y-m-d');
            $fileName = "modality_appointments_{$modalityName}_{$date}.pdf";

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function applyModalityFilters($query, Request $request)
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

        // Modality Name Filter (assuming name column in Modality model)
        if ($request->filled('modalityName')) {
            $query->whereHas('modality', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->modalityName . '%');
            });
        }
    }


    public function downloadImportTemplate(Request $request)
    {
        try {
            return Excel::download(new ModalityAppointmentsTemplateExport(), 'modality_appointments_template.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function importAppointments(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'modality_id' => 'nullable|exists:modalities,id',
            // 'skip_duplicates' => 'nullable|boolean',
            // 'validate_only' => 'nullable|boolean',
            // 'send_notifications' => 'nullable|boolean',
        ]);

        try {
            $import = new ModalityAppointmentsImport(
                $validated['modality_id'] ?? null,
                // $validated['skip_duplicates'] ?? true,
                // $validated['validate_only'] ?? false,
                // $validated['send_notifications'] ?? false
            );

            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            return response()->json([
                'success' => true,
                'message' => 'Modality appointments imported successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error importing modality appointments: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllModalities(Request $request)
    {
        try {
            $modalities = Modality::select(['id', 'name'])
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $modalities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch modalities',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function getModalityFilterSummary(Request $request)
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
        if ($request->filled('modalityName')) {
            $summary[] = "Modality: " . $request->modalityName;
        }

        return $summary;
    }

    public function forPatient(Request $request, $patientId)
    {
        try {
            // Get appointments only for this patient
            $modalityAppointments = MoalityAppointments::query()
                ->with(['patient', 'modality:id,name,specialization_id', 'modality.specialization:id,name'])
                ->whereHas('modality', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->where('patient_id', $patientId)
                ->whereNull('deleted_at')
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            // Apply filters if provided
            if ($request->filled('status') && $request->status !== 'ALL') {
                $modalityAppointments->where('status', $request->status);
            }
            if ($request->filled('date')) {
                $modalityAppointments->whereDate('appointment_date', $request->date);
            }
            if ($request->filled('filter') && $request->filter === 'today') {
                $modalityAppointments->whereDate('appointment_date', Carbon::now()->toDateString())
                    ->whereIn('status', [0, 1]); // Scheduled or Confirmed
            }

            // Fetch results without pagination
            $modalityAppointments = $modalityAppointments->get();

            return response()->json([
                'success' => true,
                'data' => ModalityAppointmentResource::collection($modalityAppointments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch modality appointments for patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingModalityAppointment(Request $request)
    {
        try {
            // Build a query to return only pending appointments (status = 3)
            $query = MoalityAppointments::query()
                ->with(['patient', 'modality:id,name,specialization_id', 'modality.specialization:id,name'])
                ->whereHas('modality', function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->where('status', AppointmentSatatusEnum::PENDING->value) // Only return pending appointments (status PENDING = 3)
                ->orderBy('appointment_date', 'asc');

            // Correctly access modalityId from the request query parameters
            $modalityId = $request->query('modalityId');
            if ($modalityId) {
                $query->where('modality_id', $modalityId);
            }
            $date = $request->query('date');
            if ($date) {
                $query->whereDate('appointment_date', $date);
            }

            // Paginate the results
            $modalityAppointments = $query->paginate(50);

            return response()->json([
                'success' => true,
                'data' => ModalityAppointmentResource::collection($modalityAppointments),
                'meta' => [
                    'current_page' => $modalityAppointments->currentPage(),
                    'per_page' => $modalityAppointments->perPage(),
                    'total' => $modalityAppointments->total(),
                    'last_page' => $modalityAppointments->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending modality appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllModalityAppointments(Request $request)
    {
        try {
            $query = DB::table('moality_appointments') // Correct table name
                ->select([
                    'moality_appointments.*',
                    'patients.firstname as patient_first_name',
                    'patients.lastname as patient_last_name',
                    'patients.id as patient_id',
                    'patients.phone',
                    'patients.dateOfBirth as patient_Date_Of_Birth',
                    'modalities.name as modality_name', // Modality name instead of doctor name
                    'modalities.id as modality_id',
                    'modalities.specialization_id as specialization_id',
                    'moality_appointments.status as appointment_status'
                ])
                ->join('patients', 'moality_appointments.patient_id', '=', 'patients.id')
                ->join('modalities', 'moality_appointments.modality_id', '=', 'modalities.id')
                ->whereNull('moality_appointments.deleted_at')
                ->whereNull('modalities.deleted_at') // Assuming soft deletes for modalities
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            if ($request->modality_id) {
                $query->where('moality_appointments.modality_id', $request->modality_id);
            }

            if ($request->filled('filter') && $request->filter === 'today') {
                $query->whereDate('appointment_date', Carbon::now()->toDateString());
            }

            if ($request->filled('status')) {
                $query->where('moality_appointments.status', $request->status);
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

            $modalityAppointments = $query->get()->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'patient_first_name' => $appointment->patient_first_name,
                    'patient_last_name' => $appointment->patient_last_name,
                    'phone' => $appointment->phone,
                    'patient_Date_Of_Birth' => $appointment->patient_Date_Of_Birth,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'modality_name' => $appointment->modality_name, // Modality name
                    'specialization_id' => $appointment->specialization_id,
                    'modality_id' => $appointment->modality_id,
                    // 'status' => $this->getStatusInfo($appointment->appointment_status)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $modalityAppointments
            ]);
        } catch (\Exception $e) {
            Log::error('Error in GetAllModalityAppointments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch modality appointments',
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
        $modalityId = $request->input('modality_id');
        $appointmentDate = $request->input('appointment_date');
        $appointmentTime = $request->input('appointment_time');

        // Base query
        $modalityAppointments = MoalityAppointments::query()
            // Filter by patient name or date of birth
            ->when($query, function ($queryBuilder) use ($query) {
                $queryBuilder->whereHas('patient', function ($patientQuery) use ($query) {
                    $patientQuery->where('Firstname', 'like', "%{$query}%")
                        ->orWhere('Lastname', 'like', "%{$query}%")
                        ->orWhere('dateOfBirth', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                });
            })
            // Filter by modality ID
            ->when($modalityId, function ($queryBuilder) use ($modalityId) {
                $queryBuilder->where('modality_id', $modalityId);
            })
            // Filter by appointment date
            ->when($appointmentDate, function ($queryBuilder) use ($appointmentDate) {
                $queryBuilder->whereDate('appointment_date', $appointmentDate);
            })
            // Filter by appointment time
            ->when($appointmentTime, function ($queryBuilder) use ($appointmentTime) {
                $queryBuilder->whereTime('appointment_time', $appointmentTime);
            })
            // Eager load the patient and modality relationship
            ->with(['patient', 'modality'])
            // Paginate results
            ->paginate(10);

        return ModalityAppointmentResource::collection($modalityAppointments);
    }
    

    public function getModalityWorkingHours($modalityId, $date)
    {
        $cacheKey = "modality_{$modalityId}_hours_{$date}";

        return Cache::remember($cacheKey, 5, function () use ($modalityId, $date) {
            $dateObj = Carbon::parse($date);
            $dayOfWeek = DayOfWeekEnum::cases()[$dateObj->dayOfWeek]->value;

            $modality = Modality::select(['id', 'time_slot_duration', 'slot_type'])
                ->findOrFail($modalityId);

            if ($modality->slot_type === 'days') {
                // If it's a Friday, no booking is allowed for 'days' type
                if ($dateObj->dayOfWeek === Carbon::FRIDAY) {
                    return []; // Modality is not available on Fridays for day-based booking
                }
                
                // For day-based booking, it's either available for the full day or not at all.
                // We'll rely on `isModalityDateAvailableForThisDate` to check for active bookings.
            return [$date]; // Return the date as a string
            }

            // Original logic for 'minutes' slot_type
            $schedules = ModalitySchedule::select('start_time', 'end_time', 'shift_period')
                ->where('modality_id', $modalityId)
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek)
                ->get();

            if ($schedules->isEmpty()) {
                return [];
            }

            $workingHours = [];
            $now = Carbon::now();
            $isToday = $dateObj->isSameDay($now);
            $bufferTime = $now->copy()->addMinutes(5);
            $dateString = $dateObj->format('Y-m-d');
            $timeSlotMinutes = (int) ($modality->time_slot_duration ?? 0);

            if ($timeSlotMinutes > 0 && $modality->slot_type === 'minutes') {
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $schedules->firstWhere('shift_period', $shift);
                    if (!$schedule) continue;

                    $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);

                    $totalMinutes = abs($endTime->diffInMinutes($startTime));
                    $totalSlots = abs(floor($totalMinutes / $timeSlotMinutes));

                    for ($i = 0; $i < $totalSlots; $i++) {
                        $slotMinutes = $i * $timeSlotMinutes;
                        $slotTime = $startTime->copy()->addMinutes($slotMinutes);

                        if ($slotTime >= $endTime) {
                            break;
                        }

                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                    }
                }
            } else {
                foreach (['morning', 'afternoon'] as $shift) {
                    $schedule = $schedules->firstWhere('shift_period', $shift);
                    if (!$schedule) continue;

                    $startTime = Carbon::parse($dateString . ' ' . $schedule->start_time);
                    $endTime = Carbon::parse($dateString . ' ' . $schedule->end_time);
                    $patientsForShift = (int)($schedule->number_of_patients_per_day ?? 0);

                    if ($patientsForShift <= 0) continue;

                    if ($patientsForShift == 1) {
                        if (!$isToday || $startTime->greaterThan($bufferTime)) {
                            $workingHours[] = $startTime->format('H:i');
                        }
                        continue;
                    }

                    $totalDuration = abs($endTime->diffInMinutes($startTime));
                    $slotDuration = ($patientsForShift > 1) ? abs($totalDuration / ($patientsForShift - 1)) : $totalDuration;


                    for ($i = 0; $i < $patientsForShift; $i++) {
                        $minutesToAdd = round($i * $slotDuration);
                        $slotTime = $startTime->copy()->addMinutes($minutesToAdd);

                        if (!$isToday || $slotTime->greaterThan($bufferTime)) {
                            $workingHours[] = $slotTime->format('H:i');
                        }
                    }
                }
            }

            return array_unique($workingHours);
        });
    }
    

    public function getModalityUserPermissions(Request $request)
{
    try {
        $modalityId = $request->query('modality_id');
        
        $query = AppointmentModalityForce::query()
            ->select(['user_id', 'modality_id', 'is_able_to_force'])
            ->with(['user:id,name,email', 'modality:id,name']);
            
        if ($modalityId) {
            $query->where('modality_id', $modalityId);
        }
        
        $permissions = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch modality user permissions',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getModalityUserForceAbility(Request $request)
{
    $validated = $request->validate([
        'modality_id' => 'required|exists:modalities,id',
        'user_id' => 'required|exists:users,id',
    ]);

    try {
        $userId = $validated['user_id'];
        $modalityId = $validated['modality_id'];
        
        // Check if user has admin privileges
        $user = \App\Models\User::find($userId);
        $isAdmin = in_array($user->role, ['admin', 'SuperAdmin']);
        
        // Check modality-specific force permissions
        $hasModalityPermission = AppointmentModalityForce::where('modality_id', $modalityId)
            ->where('user_id', $userId)
            ->where('is_able_to_force', true)
            ->exists();

        $isAbleToForce = $isAdmin || $hasModalityPermission;

        return response()->json([
            'success' => true,
            'data' => [
                'is_able_to_force' => $isAbleToForce
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to check force permissions',
            'error' => $e->getMessage()
        ], 500);
    }
}
/**
 * Update or create modality user permission
 */
public function updateModalityUserPermission(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'modality_id' => 'required|exists:modalities,id',
        'is_able_to_force' => 'required|boolean'
    ]);

    try {
        $permission = AppointmentModalityForce::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'modality_id' => $validated['modality_id']
            ],
            [
                'is_able_to_force' => $validated['is_able_to_force']
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update permission',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function getBookedSlots($modalityId, $date)
    {
        $modality = Modality::find($modalityId);
        if (!$modality) {
            return [];
        }

        if ($modality->slot_type === 'days') {
            // For 'days' slot type, check if the specific date falls within any active booking period
            return MoalityAppointments::where('modality_id', $modalityId)
                ->where(function ($query) use ($date) {
                    $query->where('appointment_date', '<=', $date)
                        ->where('end_date', '>=', $date);
                })
                ->whereNotIn('status', $this->excludedStatuses)
                ->pluck('appointment_date') // Just checking existence of booking for the day
                ->map(fn ($d) => 'full_day_available') // Return a placeholder to indicate booked status
                ->unique()
                ->toArray();
        } else {
            // Original logic for 'minutes' slot_type
            return MoalityAppointments::where('modality_id', $modalityId)
                ->whereDate('appointment_date', $date)
                ->whereNotIn('status', $this->excludedStatuses)
                ->pluck('appointment_time')
                ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
                ->unique()
                ->toArray();
        }
    }

    public function forceModalityAppointment(Request $request)
    {
        $validated = $request->validate([
            'modality_id' => 'required|exists:modalities,id',
            'days' => 'nullable|integer',
            'date' => 'nullable|date_format:Y-m-d', // Accept date directly
        ]);

        $modalityId = $validated['modality_id'];
        $days = (int)($validated['days'] ?? 0);
        $dateInput = $validated['date'] ?? null;

        try {
            $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : Carbon::now()->addDays($days)->format('Y-m-d');
            $dateCarbon = Carbon::parse($date);
            $dayOfWeek = DayOfWeekEnum::cases()[$dateCarbon->dayOfWeek]->value;

            $modality = Modality::find($modalityId);

            // Fetch schedules for the given date (specific date schedule overrides recurring schedule)
            $schedules = ModalitySchedule::where('modality_id', $modalityId)
                ->where('is_active', true)
                ->where(function ($query) use ($date, $dayOfWeek) {
                    $query->where('date', $date) // Specific date schedule
                        ->orWhere(function ($q) use ($dayOfWeek) {
                            $q->whereNull('date') // Recurring schedule
                                ->where('day_of_week', $dayOfWeek);
                        });
                })
                ->get();

            // Determine time slot minutes based on modality's setting or calculated from schedules
            $timeSlotMinutes = $this->calculateModalityTimeSlotMinutes($modality, $date);

            $gapSlots = [];
            $additionalSlots = [];

            // For 'days' slot type, 'force' means just providing the starting date if it's available.
            // No granular time slots are generated.
            if ($modality->slot_type === 'days') {
                if ($this->isModalityDateAvailableForThisDate($modalityId, $dateCarbon)) {
                    $additionalSlots = ['full_day_available']; // Indicate that the day is conceptually available for a booking
                }
            } elseif ($schedules->isEmpty()) {
                // If no regular schedules, generate slots based on AppointmentModalityForce or default 8:00-17:00
                $additionalSlots = $this->generateDefaultModalitySlots($modalityId, $date, $timeSlotMinutes);
            } else {
                // Handle morning and afternoon schedules for 'minutes' type
                $morningSchedule = $schedules->firstWhere('shift_period', 'morning');
                $afternoonSchedule = $schedules->firstWhere('shift_period', 'afternoon');

                list($gapSlots, $additionalSlots) = $this->handleModalitySchedules(
                    $morningSchedule,
                    $afternoonSchedule,
                    $date,
                    $timeSlotMinutes,
                    $modalityId
                );
            }

            return response()->json([
                'gap_slots' => $gapSlots,
                'additional_slots' => $additionalSlots,
                'next_available_date' => $date,
                'time_slot_minutes' => $timeSlotMinutes, // Still return this for consistency, even if not used by 'days'
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating modality slots: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error calculating modality appointment slots: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function calculateModalityTimeSlotMinutes($modality, $date)
    {
        if ($modality->slot_type === 'days') {
            return (int) $modality->time_slot_duration; // This is the max booking duration in days
        }

        $timeSlotMinutes = is_numeric($modality->time_slot_duration) ? (int) $modality->time_slot_duration : 0;

        if ($timeSlotMinutes > 0 && $modality->slot_type === 'minutes') {
            return $timeSlotMinutes;
        }

        $dayOfWeek = DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value;

        $schedules = ModalitySchedule::where('modality_id', $modality->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        $morningPatients = 0;
        $afternoonPatients = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->shift_period === 'morning' && $schedule->number_of_patients_per_day !== null) {
                $morningPatients += $schedule->number_of_patients_per_day;
            }
            if ($schedule->shift_period === 'afternoon' && $schedule->number_of_patients_per_day !== null) {
                $afternoonPatients += $schedule->number_of_patients_per_day;
            }
        }

        $totalPatients = $morningPatients + $afternoonPatients;

        $totalAvailableTime = $this->calculateTotalModalityAvailableTime($modality->id, $date);

        if ($totalPatients > 0) {
            $timeSlotMinutes = (int)(abs($totalAvailableTime) / $totalPatients);
        } else {
            $timeSlotMinutes = 30; // Default to 30 minutes
        }

        return $timeSlotMinutes;
    }

    private function calculateTotalModalityAvailableTime($modalityId, $date)
    {
        // For 'days' slot type, this function is not directly applicable for granular time,
        // but might be used if 'number_of_patients_per_day' is interpreted differently for days.
        // For now, it will only calculate for 'minutes' based schedules.
        $modality = Modality::find($modalityId);
        if ($modality->slot_type === 'days') {
            return 24 * 60; // Representing a full day in minutes for conceptual division
        }

        $schedules = ModalitySchedule::where('modality_id', $modalityId)
            ->where('is_active', true)
            ->where('day_of_week', DayOfWeekEnum::cases()[Carbon::parse($date)->dayOfWeek]->value)
            ->get();

        $totalAvailableTime = 0;

        foreach ($schedules as $schedule) {
            $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
            $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
            $totalAvailableTime += $endTime->diffInMinutes($startTime);
        }

        return $totalAvailableTime;
    }

    private function generateDefaultModalitySlots($modalityId, $date, $timeSlotMinutes)
    {
        $modality = Modality::find($modalityId);

        if ($modality->slot_type === 'days') {
            // If it's a Friday, no default slots for 'days' type
            if (Carbon::parse($date)->dayOfWeek === Carbon::FRIDAY) {
                return [];
            }
            // For 'days' slot type, the slot is the entire day.
            // We just return one indicator if the day is available.
            return $this->isModalityDateAvailableForThisDate($modalityId, Carbon::parse($date)) ? ['full_day_available'] : [];
        }

        // Original logic for 'minutes' slot_type
        $forceAppointment = AppointmentModalityForce::where('modality_id', $modalityId)->first();
        $defaultStartTime = $forceAppointment->start_time ?? '08:00';
        $defaultEndTime = $forceAppointment->end_time ?? '17:00';

        if ($forceAppointment && $forceAppointment->start_time != null && $forceAppointment->end_time != null) {
            $numberOfPatients = $forceAppointment->number_of_patients;
            if ($numberOfPatients > 0) {
                $timeSlotMinutes = abs(Carbon::parse($forceAppointment->end_time)->diffInMinutes(Carbon::parse($forceAppointment->start_time)) / $numberOfPatients);
            } else {
                $timeSlotMinutes = 30; // Fallback if number_of_patients is zero
            }
        }

        $startTime = Carbon::parse($date . ' ' . $defaultStartTime);
        $endTime = Carbon::parse($date . ' ' . $defaultEndTime);
        $slots = [];

        $bookedSlots = $this->getBookedSlots($modalityId, $date);

        $currentTime = clone $startTime;
        while ($currentTime < $endTime) {
            $slotTime = $currentTime->format('H:i');

            if (!in_array($slotTime, $bookedSlots)) {
                $slots[] = $slotTime;
            }

            $currentTime->addMinutes($timeSlotMinutes);
        }

        return $slots;
    }

    public function printModalityTicket(Request $request)
    {
        try {
            $data = $request->validate([
                'patient_first_name' => 'required|string|max:255',
                'patient_last_name' => 'required|string|max:255',
                'modality_name' => 'nullable|string|max:255',
                'modality_id' => 'nullable|integer',
                'appointment_date' => 'required',
                'appointment_time' => 'required',
                'description' => 'nullable|string|max:1000'
            ]);
            if ($data['modality_id']) {
                $modality = Modality::find($data['modality_id']);
                if ($modality) {
                    $data['modality_name'] = $modality->name;
                }
            }
            $data['user_name'] = Auth::user()->name;

            $customPaper = array(0, 0, 226.77, 283.46); // 8cm width x 10cm height in points

            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'XB Zar', // Ensure this font is available or remove if not
                'chroot' => storage_path('app/public'),
                'paperSize' => $customPaper,
                'margin-top' => 2,
                'margin-right' => 2,
                'margin-bottom' => 2,
                'margin-left' => 2,
                'dpi' => 203 // Standard DPI for thermal printers
            ])->loadView('tickets.modality_appointment', $data); // You'll need to create this Blade view

            $pdf->setOption('grayscale', true);

            return $pdf->download('modality_ticket.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate modality ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function printModalityConfirmationTicket(Request $request)
    {
        try {
            $data = $request->validate([
                'patient_first_name' => 'required|string|max:255',
                'patient_last_name' => 'required|string|max:255',
                'specialization_id' => 'nullable|max:255',
                'modality_name' => 'required|string|max:255',
                'appointment_date' => 'required',
                'appointment_time' => 'required|date_format:H:i',
                'description' => 'nullable|string|max:1000'
            ]);

            $data['specialization_name'] = \App\Models\Specialization::find($data['specialization_id'])->name ?? 'N/A';
            $data['user_name'] = Auth::user()->name;

            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'XB Zar', // Ensure this font is available or remove if not
                'chroot' => storage_path('app/public'),
                'margin-top' => 2,
                'margin-right' => 2,
                'margin-bottom' => 2,
                'margin-left' => 2,
            ])->loadView('tickets.modality_confirmation', $data); // You'll need to create this Blade view

            $pdf->setOption('grayscale', true);

            return $pdf->download('modality_confirmation_ticket.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate modality confirmation ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function handleModalitySchedules($morningSchedule, $afternoonSchedule, $date, $timeSlotMinutes, $modalityId)
    {
        $gapSlots = [];
        $additionalSlots = [];

        // This function is primarily for 'minutes' slot_type
        $modality = Modality::find($modalityId);
        if ($modality->slot_type === 'days') {
            return [[], []]; // No time-based gaps/additional slots for 'days'
        }

        $bookedSlots = $this->getBookedSlots($modalityId, $date);

        if ($morningSchedule && !$afternoonSchedule) {
            $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
            $additionalSlots = $this->generateSlotsAfterModalityTime($morningEndTime, $timeSlotMinutes, $modalityId, $date);
        } elseif (!$morningSchedule && $afternoonSchedule) {
            $afternoonEndTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);
            $additionalSlots = $this->generateSlotsAfterModalityTime($afternoonEndTime, $timeSlotMinutes, $modalityId, $date);
        } elseif ($morningSchedule && $afternoonSchedule) {
            $morningEndTime = Carbon::parse($date . ' ' . $morningSchedule->end_time);
            $afternoonStartTime = Carbon::parse($date . ' ' . $afternoonSchedule->start_time);
            $afternoonEndTime = Carbon::parse($date . ' ' . $afternoonSchedule->end_time);

            $currentTime = clone $morningEndTime;
            while ($currentTime < $afternoonStartTime) {
                $slotTime = $currentTime->format('H:i');
                if (!in_array($slotTime, $bookedSlots)) {
                    $gapSlots[] = $slotTime;
                }
                $currentTime->addMinutes($timeSlotMinutes);
            }

            $additionalSlots = $this->generateSlotsAfterModalityTime($afternoonEndTime, $timeSlotMinutes, $modalityId, $date);
        }

        return [$gapSlots, $additionalSlots];
    }

    private function generateSlotsAfterModalityTime($endTime, $timeSlotMinutes, $modalityId, $date)
    {
        $modality = Modality::find($modalityId);
        if ($modality->slot_type === 'days') {
            return []; // Not applicable for 'days' slot type
        }

        $slots = [];
        $currentTime = clone $endTime;
        $bookedSlots = $this->getBookedSlots($modalityId, $date);

        for ($i = 0; $i < 20; $i++) {
            $currentTime->addMinutes($timeSlotMinutes);
            $slotTime = $currentTime->format('H:i');
            if (!in_array($slotTime, $bookedSlots)) {
                $slots[] = $slotTime;
            }
        }
        return $slots;
    }


   public function getModalityAppointment($modalityId, $modalityAppointmentId)
{
    try {
        $modalityAppointment = MoalityAppointments::with([
            'patient:id,Firstname,Lastname,phone,dateOfBirth',
            'modality:id,name,slot_type,time_slot_duration'
        ])
            ->where('modality_id', $modalityId)
            ->where('id', $modalityAppointmentId)
            ->first();

        if (!$modalityAppointment) {
            return response()->json([
                'success' => false,
                'message' => 'Modality appointment not found.'
            ], 404);
        }

        // Fix: Only parse appointment_time if it's a valid time
        $appointmentTime = $modalityAppointment->appointment_time;
        if ($appointmentTime && $appointmentTime !== 'full_day_available') {
            $appointmentTime = Carbon::parse($appointmentTime)->format('H:i');
        } else {
            $appointmentTime = null; // or 'Full Day' if you want to display that
        }

        $appointmentData = [
            'id' => $modalityAppointment->id,
            'modality_id' => $modalityAppointment->modality_id,
            'appointment_date' => $modalityAppointment->appointment_date,
            'appointment_time' => $appointmentTime,
            'notes' => $modalityAppointment->notes,
            'status' => $modalityAppointment->status,
            'duration_days' => $modalityAppointment->duration_days,
            'end_date' => $modalityAppointment->end_date,
            'patient' => [
                'id' => $modalityAppointment->patient->id,
                'first_name' => $modalityAppointment->patient->Firstname,
                'last_name' => $modalityAppointment->patient->Lastname,
                'phone' => $modalityAppointment->patient->phone,
                'date_of_birth' => $modalityAppointment->patient->dateOfBirth,
                // Also provide alternative field names for compatibility
                'firstname' => $modalityAppointment->patient->Firstname,
                'lastname' => $modalityAppointment->patient->Lastname,
                'dateOfBirth' => $modalityAppointment->patient->dateOfBirth,
            ],
            'modality' => $modalityAppointment->modality
        ];

        return response()->json([
            'success' => true,
            'data' => $appointmentData
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching modality appointment: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch appointment details.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function checkModalityAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'modality_id' => 'required|exists:modalities,id',
                'date' => 'nullable|date_format:Y-m-d',
                'days' => 'nullable|integer',
                'range' => 'nullable|integer',
                'include_slots' => 'nullable|in:true,false,1,0',
                'duration_days' => 'nullable|integer|min:1',
            ]);

            $modalityId = $validated['modality_id'];
            $modality = Modality::select(['id', 'slot_type', 'time_slot_duration'])->findOrFail($modalityId);

            // Determine the start date for the search
            if (isset($validated['date'])) {
                $startDate = Carbon::parse($validated['date']);
            } elseif (isset($validated['days'])) {
                $startDate = Carbon::now()->addDays((int)$validated['days']);
            } else {
                $startDate = Carbon::now();
            }

            // Get range if provided, default to 0
            $range = isset($validated['range']) ? (int) $validated['range'] : 0;

            // For days slot type, find next available period
            if ($modality->slot_type === 'days') {
                $durationDays = $validated['duration_days'] ?? $validated['days'] ?? 1;

                // Validate duration doesn't exceed modality limit
                if ($durationDays > $modality->time_slot_duration) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duration exceeds maximum allowed days for this modality',
                        'max_duration' => $modality->time_slot_duration
                    ], 422);
                }

                $nextAvailableDate = $this->findNextAvailableDaysPeriod($startDate, $modalityId, $durationDays, $range);
            } else {
                // Original logic for minutes slot type
                if ($range > 0) {
                    $nextAvailableDate = $this->findNextAvailableModalityAppointmentWithinRange($startDate, $modalityId, $range);
                } else {
                    $nextAvailableDate = $this->findNextAvailableModalityAppointment($startDate, $modalityId);
                }
            }

            if (!$nextAvailableDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'No available slots found',
                    'data' => [
                        'current_date' => $startDate->format('Y-m-d'),
                        'next_available_date' => null,
                        'period' => null,
                        'available_slots' => []
                    ]
                ]);
            }

            // Calculate period difference from current date
            $daysDifference = abs($nextAvailableDate->diffInDays(Carbon::now()));
            $period = $this->calculatePeriod($daysDifference);

            // Build the response
            $response = [
                'current_date' => $startDate->format('Y-m-d'),
                'next_available_date' => $nextAvailableDate->format('Y-m-d'),
                'period' => $period,
            ];

            $includeSlots = isset($validated['include_slots']) &&
                in_array($validated['include_slots'], ['true', '1', true, 1]);

            if ($includeSlots) {
                // Always get time slots - even for days slot type, users need to select start time
                $workingHours = $this->getModalityWorkingHours($modalityId, $nextAvailableDate->format('Y-m-d'));

                if ($modality->slot_type === 'days') {
                    // For days type, show all working hours as potential start times
                    $response['available_slots'] = $workingHours;
                    $response['duration_days'] = $durationDays ?? 1;
                    $response['slot_type'] = 'days';
                } else {
                    // For minutes type, exclude already booked slots
                    $bookedSlots = $this->getBookedSlots($modalityId, $nextAvailableDate->format('Y-m-d'));
                    $availableSlots = array_diff($workingHours, $bookedSlots);
                    $response['available_slots'] = array_values($availableSlots);
                    $response['slot_type'] = 'minutes';
                }
            } else {
                $response['available_slots'] = [];
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking modality availability: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking modality availability.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find the next available appointment date for a modality
     */
    private function findNextAvailableModalityAppointment(Carbon $startDate, $modalityId, $range = 0)
    {
        $currentDate = clone $startDate;
        $endOfSearchPeriod = $range > 0 ? $currentDate->copy()->addDays($range) : Carbon::now()->addYear();

        while ($currentDate->lte($endOfSearchPeriod)) {
            // Skip Fridays
            if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                $currentDate->addDay();
                continue;
            }

            // Check if this date is available for the modality
            if ($this->isModalityDateAvailableForThisDate($modalityId, $currentDate)) {
                // Check if there are available slots for this date
                $workingHours = $this->getModalityWorkingHours($modalityId, $currentDate->format('Y-m-d'));
                $bookedSlots = $this->getBookedSlots($modalityId, $currentDate->format('Y-m-d'));
                $availableSlots = array_diff($workingHours, $bookedSlots);

                if (!empty($availableSlots)) {
                    return $currentDate;
                }
            }

            $currentDate->addDay();
        }

        return null;
    }

    /**
     * Find the next available appointment within a specific range
     */
    private function findNextAvailableModalityAppointmentWithinRange(Carbon $startDate, $modalityId, $range)
    {
        return $this->findNextAvailableModalityAppointment($startDate, $modalityId, $range);
    }

    /**
     * Check if a modality date is available for appointments
     */

    /**
     * Check if a modality date is available for appointments
     */
    private function isModalityDateAvailableForThisDate($modalityId, Carbon $date)
    {
        try {
            // For now, let's use a simple check - you can expand this based on your business logic

            // Check if it's not a Friday (typically excluded for modalities)
            if ($date->dayOfWeek === Carbon::FRIDAY) {
                return false;
            }

            // Check if it's not a weekend (optional - depends on your business rules)
            if ($date->isWeekend()) {
                return false; // or true if you allow weekend appointments
            }

            // Check if the date is not in the past
            if ($date->lt(Carbon::now()->startOfDay())) {
                return false;
            }

            // You can add more complex logic here, such as:
            // - Checking modality working schedules
            // - Checking facility availability
            // - Checking excluded dates

            return true;
        } catch (\Exception $e) {
            Log::error('Error checking modality date availability: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get working hours for a modality on a specific date
     */
    // private function getModalityWorkingHours($modalityId, $date)
    // {
    //      try {
    //          $modality = Modality::findOrFail($modalityId);
    //          $workingHours = [];

    //          // Default working hours (8:00 AM to 5:00 PM with lunch break)
    //          $startTime = Carbon::createFromFormat('H:i', '08:00');
    //          $lunchStart = Carbon::createFromFormat('H:i', '12:00');
    //          $lunchEnd = Carbon::createFromFormat('H:i', '13:00');
    //          $endTime = Carbon::createFromFormat('H:i', '17:00');

    //          // Get the slot duration from modality (default 20 minutes)
    //          $slotDuration = $modality->time_slot_duration ?? 20;

    //          $currentTime = clone $startTime;

    //          while ($currentTime->lt($endTime)) {
    //              // Skip lunch break
    //              if ($currentTime->gte($lunchStart) && $currentTime->lt($lunchEnd)) {
    //                  $currentTime->addMinutes($slotDuration);
    //                  continue;
    //              }

    //              $workingHours[] = $currentTime->format('H:i');
    //              $currentTime->addMinutes($slotDuration);
    //          }

    //          return $workingHours;
    //      } catch (\Exception $e) {
    //          Log::error('Error getting modality working hours: ' . $e->getMessage());
    //          return [];
    //      }
    // }

    /**
     * Get booked slots for a modality on a specific date
     */
    // private function getBookedSlots($modalityId, $date)
    // {
    //      try {
    //          $bookedAppointments = MoalityAppointments::where('modality_id', $modalityId)
    //              ->whereDate('appointment_date', $date)
    //              ->whereNotIn('status', [AppointmentSatatusEnum::CANCELED->value])
    //              ->get();

    //          $bookedSlots = [];
    //          foreach ($bookedAppointments as $appointment) {
    //              if ($appointment->appointment_time) {
    //                  $bookedSlots[] = Carbon::parse($appointment->appointment_time)->format('H:i');
    //              }
    //          }

    //          return $bookedSlots;
    //      } catch (\Exception $e) {
    //          Log::error('Error getting booked slots: ' . $e->getMessage());
    //          return [];
    //      }
    // }

    /**
     * Calculate period description from days difference
     */
    private function calculatePeriod($daysDifference)
    {
        if ($daysDifference == 0) {
            return 'Today';
        } elseif ($daysDifference == 1) {
            return 'Tomorrow';
        } elseif ($daysDifference <= 7) {
            return $daysDifference . ' day(s)';
        } elseif ($daysDifference <= 30) {
            $weeks = floor($daysDifference / 7);
            $remainingDays = $daysDifference % 7;

            if ($remainingDays == 0) {
                return $weeks . ' week(s)';
            } else {
                return $weeks . ' week(s) and ' . $remainingDays . ' day(s)';
            }
        } else {
            $months = floor($daysDifference / 30);
            $remainingDays = $daysDifference % 30;

            if ($remainingDays == 0) {
                return $months . ' month(s)';
            } else {
                return $months . ' month(s) and ' . $remainingDays . ' day(s)';
            }
        }
    }

    /**
     * Find next available period for days slot type
     */
    private function findNextAvailableDaysPeriod(Carbon $startDate, $modalityId, $durationDays, $range = 0)
    {
        $currentDate = clone $startDate;
        $endOfSearchPeriod = $range > 0 ? $currentDate->copy()->addDays($range) : Carbon::now()->addYear();

        while ($currentDate->lte($endOfSearchPeriod)) {
            // Skip Fridays for days slot type
            if ($currentDate->dayOfWeek === Carbon::FRIDAY) {
                $currentDate->addDay();
                continue;
            }

            // Check if the entire period is available
            if ($this->isDaysPeriodAvailable($modalityId, $currentDate, $durationDays)) {
                return $currentDate;
            }

            $currentDate->addDay();
        }

        return null;
    }


    /**
     * Check if a period of days is available for booking
     */
    private function isDaysPeriodAvailable($modalityId, Carbon $startDate, $durationDays)
    {
        // For duration 1: check only the start date
        // For duration 2+: check start date + (duration-1) additional days
        for ($i = 0; $i < $durationDays; $i++) {
            $checkDate = $startDate->copy()->addDays($i);

            // Skip Fridays
            if ($checkDate->dayOfWeek === Carbon::FRIDAY) {
                return false;
            }

            // Check if date is available
            if (!$this->isModalityDateAvailableForThisDate($modalityId, $checkDate)) {
                return false;
            }

            // Check for existing bookings on this date
            $existingBookings = MoalityAppointments::where('modality_id', $modalityId)
                ->whereDate('appointment_date', $checkDate->format('Y-m-d'))
                ->whereNotIn('status', $this->excludedStatuses)
                ->exists();

            if ($existingBookings) {
                return false;
            }
        }

        return true;
    }



    // Update the store method to handle canceled appointment rebooking
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'modality_id' => 'required|exists:modalities,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'nullable',
            'description' => 'nullable|string|max:1000',
            'duration_days' => 'nullable|integer|min:1',
            'is_rebook_canceled' => 'nullable|boolean', // New field to identify canceled appointment rebooking
        ]);

        $modality = Modality::select(['id', 'slot_type', 'time_slot_duration'])->findOrFail($validated['modality_id']);
        $appointmentDate = Carbon::parse($validated['appointment_date']);
        $appointmentTime = $validated['appointment_time'] ?? null;

        if ($modality->slot_type === 'days') {
            $durationDays = $validated['duration_days'] ?? 1;

            // Apply specific rules for 'days' slot_type
            if ($appointmentDate->dayOfWeek === Carbon::FRIDAY) {
                return response()->json([
                    'message' => 'Booking on Friday is not allowed for this modality type.',
                    'errors' => ['appointment_date' => ['You cannot book on a Friday.']]
                ], 422);
            }

            if ($appointmentDate->dayOfWeek !== Carbon::THURSDAY) {
                if ($durationDays > 1) {
                    return response()->json([
                        'message' => 'You can only book one day at a time for this modality type, except on Thursdays.',
                        'errors' => ['duration_days' => ['You cannot book more than one day.']]
                    ], 422);
                }
            } else { // It's a Thursday
                if ($durationDays > 3) {
                    return response()->json([
                        'message' => 'On Thursdays, you can book a maximum of three days.',
                        'errors' => ['duration_days' => ['You can book a maximum of three days on Thursday.']]
                    ], 422);
                }
            }


            // If rebooking a canceled appointment, check for the exact canceled appointment
            if ($validated['is_rebook_canceled'] ?? false) {
                // Find the canceled appointment for this date
                $canceledAppointment = MoalityAppointments::where('modality_id', $validated['modality_id'])
                    ->where('status', AppointmentSatatusEnum::CANCELED->value)
                    ->whereDate('appointment_date', $appointmentDate->format('Y-m-d'))
                    ->first();

                if (!$canceledAppointment) {
                    return response()->json([
                        'message' => 'No canceled appointment found for the selected date.',
                        'errors' => ['appointment_date' => ['No canceled appointment available for rebooking.']]
                    ], 422);
                }

                // Use the original duration from the canceled appointment
                $durationDays = $canceledAppointment->duration_days ?? 1;

                // Delete the canceled appointment and create new one
                $canceledAppointment->delete();
            } else {
                // Normal booking logic - check for conflicts
                $bookingDates = [];
                for ($i = 0; $i < $durationDays; $i++) {
                    $currentDay = $appointmentDate->copy()->addDays($i);
                    $bookingDates[] = $currentDay;
                }

                // Check each date for existing bookings
                foreach ($bookingDates as $currentDate) {
                    $existingAppointment = MoalityAppointments::where('modality_id', $validated['modality_id'])
                        ->whereDate('appointment_date', $currentDate->format('Y-m-d'))
                        ->whereNotIn('status', $this->excludedStatuses)
                        ->first();

                    if ($existingAppointment) {
                        return response()->json([
                            'message' => "Date {$currentDate->format('Y-m-d')} is already booked.",
                            'errors' => ['appointment_date' => ["The date {$currentDate->format('Y-m-d')} is already booked."]]
                        ], 422);
                    }
                }
            }

            // Create the appointment(s)
            $appointments = [];
            for ($i = 0; $i < $durationDays; $i++) {
                $currentDate = $appointmentDate->copy()->addDays($i);

                $appointments[] = MoalityAppointments::create([
                    'patient_id' => $validated['patient_id'],
                    'modality_id' => $validated['modality_id'],
                    'appointment_date' => $currentDate->format('Y-m-d'),
                    'appointment_time' => $appointmentTime,
                    'notes' => $validated['description'] ?? null,
                    'status' => AppointmentSatatusEnum::SCHEDULED->value,
                    'created_by' => Auth::id(),
                    'duration_days' => $durationDays,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully created {$durationDays} day(s) appointment.",
                'data' => new ModalityAppointmentResource($appointments[0])
            ]);
        } else {
            // Original logic for minutes slot type
            if (!MoalityAppointments::isSlotAvailable(
                $validated['modality_id'],
                $validated['appointment_date'],
                $appointmentTime,
                $this->excludedStatuses
            )) {
                return response()->json([
                    'message' => 'This time slot is already booked.',
                    'errors' => ['appointment_time' => ['The selected time slot is no longer available.']]
                ], 422);
            }

            $modalityAppointment = MoalityAppointments::create([
                'patient_id' => $validated['patient_id'],
                'modality_id' => $validated['modality_id'],
                'appointment_date' => $validated['appointment_date'],
                'appointment_time' => $appointmentTime,
                'notes' => $validated['description'] ?? null,
                'status' => AppointmentSatatusEnum::SCHEDULED->value,
                'created_by' => Auth::id(),
            ]);

            return new ModalityAppointmentResource($modalityAppointment);
        }
    }
    public function nextModalityAppointment(Request $request, $modalityAppointmentId)
    {
        $existingModalityAppointment = MoalityAppointments::findOrFail($modalityAppointmentId);
        $existingModalityAppointment->update(['status' => AppointmentSatatusEnum::DONE->value]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'modality_id' => 'required|exists:modalities,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'appointment_time' => 'nullable|date_format:H:i', // Time can be null for 'days' type
            'description' => 'nullable|string|max:1000',
            'duration_days' => 'nullable|integer|min:1', // New field for 'days' slot_type
        ]);

        $modality = Modality::select(['id', 'slot_type', 'time_slot_duration'])->findOrFail($validated['modality_id']);

        $appointmentDate = Carbon::parse($validated['appointment_date']);
        $appointmentTime = $validated['appointment_time'] ?? null;
        $endDate = null;

        if ($modality->slot_type === 'days') {
            $durationDays = $validated['duration_days'] ?? 1;

            // Apply specific rules for 'days' slot_type
            if ($appointmentDate->dayOfWeek === Carbon::FRIDAY) {
                return response()->json([
                    'message' => 'Booking on Friday is not allowed for this modality type.',
                    'errors' => ['appointment_date' => ['You cannot book on a Friday.']]
                ], 422);
            }

            if ($appointmentDate->dayOfWeek !== Carbon::THURSDAY) {
                if ($durationDays > 1) {
                    return response()->json([
                        'message' => 'You can only book one day at a time for this modality type, except on Thursdays.',
                        'errors' => ['duration_days' => ['You cannot book more than one day.']]
                    ], 422);
                }
            } else { // It's a Thursday
                if ($durationDays > 3) {
                    return response()->json([
                        'message' => 'On Thursdays, you can book a maximum of three days.',
                        'errors' => ['duration_days' => ['You can book a maximum of three days on Thursday.']]
                    ], 422);
                }
            }


            if (empty($validated['duration_days'])) {
                return response()->json([
                    'message' => 'Duration in days is required for this modality type.',
                    'errors' => ['duration_days' => ['The duration in days is required.']]
                ], 422);
            }
            if ($validated['duration_days'] > $modality->time_slot_duration) {
                return response()->json([
                    'message' => "Maximum booking duration for this modality is {$modality->time_slot_duration} day(s).",
                    'errors' => ['duration_days' => ["Cannot book for more than {$modality->time_slot_duration} day(s)."]]
                ], 422);
            }
            $endDate = $appointmentDate->copy()->addDays($validated['duration_days'] - 1)->format('Y-m-d');

            for ($i = 0; $i < $validated['duration_days']; $i++) {
                $currentDay = $appointmentDate->copy()->addDays($i);
                if ($currentDay->dayOfWeek === Carbon::FRIDAY) {
                    return response()->json([
                        'message' => 'Booking cannot include Fridays for this modality type.',
                        'errors' => ['appointment_date' => ['The booking period cannot include a Friday.']]
                    ], 422);
                }
            }
        }

        // Check if the new slot is already booked
        if (
            !MoalityAppointments::isSlotAvailable(
                $validated['modality_id'],
                $validated['appointment_date'],
                $appointmentTime,
                $this->excludedStatuses,
                $endDate // Pass endDate for 'days' slot_type check
            )
        ) {
            return response()->json([
                'message' => 'This time slot or period is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot/period is no longer available.']]
            ], 422);
        }

        $newModalityAppointment = MoalityAppointments::create([
            'patient_id' => $validated['patient_id'],
            'modality_id' => $validated['modality_id'],
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $appointmentTime,
            'end_date' => $endDate,
            'notes' => $validated['description'] ?? null,
            'status' => AppointmentSatatusEnum::SCHEDULED->value, // Default to Scheduled
            'created_by' => Auth::id(),
        ]);

        return new ModalityAppointmentResource($newModalityAppointment);
    }

    public function show($id)
    {
        $modalityAppointment = MoalityAppointments::findOrFail($id);
        return new ModalityAppointmentResource($modalityAppointment);
    }



    public function availableModalityAppointments($modalityId)
    {
        try {
            $modality = Modality::select(['id', 'slot_type', 'time_slot_duration'])->findOrFail($modalityId);
            $now = Carbon::now();

            // Get canceled appointments grouped by date and calculate remaining days
            $canceledAppointments = MoalityAppointments::where('modality_id', $modalityId)
                ->where('status', AppointmentSatatusEnum::CANCELED->value)
                ->whereDate('appointment_date', '>=', $now->toDateString())
                ->get()
                ->groupBy(function ($appointment) {
                    return Carbon::parse($appointment->appointment_date)->format('Y-m-d');
                })
                ->map(function ($appointments, $date) use ($modality, $modalityId) {
                    $firstAppointment = $appointments->first();
                    $originalDuration = $firstAppointment->duration_days ?? 1;

                    // Calculate how many days are still available for this canceled appointment
                    $availableDuration = $this->calculateRemainingCanceledDays($modalityId, $date, $originalDuration);

                    // Only include if there are still days available
                    if ($availableDuration > 0) {
                        return [
                            'date' => $date,
                            'original_duration' => $originalDuration,
                            'available_duration' => $availableDuration,
                            'available_times' => $modality->slot_type === 'days'
                                ? ['Full Day Available']
                                : $appointments->map(function ($appointment) {
                                    return $appointment->appointment_time
                                        ? Carbon::parse($appointment->appointment_time)->format('H:i')
                                        : 'Full Day';
                                })->toArray()
                        ];
                    }
                    return null;
                })
                ->filter() // Remove null entries
                ->values()
                ->toArray();

            // Extract canceled appointment dates for exclusion from next appointment search
            $canceledDates = array_column($canceledAppointments, 'date');

            // Find next available appointment date
            $nextAvailableDate = null;
            $searchDate = $now->copy();
            $maxSearchDays = 30;

            for ($i = 0; $i < $maxSearchDays; $i++) {
                // Skip Fridays
                if ($searchDate->dayOfWeek === Carbon::FRIDAY) {
                    $searchDate->addDay();
                    continue;
                }

                $currentDateString = $searchDate->format('Y-m-d');

                // Skip if this date is in the canceled appointments list
                if (in_array($currentDateString, $canceledDates)) {
                    $searchDate->addDay();
                    continue;
                }

                // Check if this date is available for the modality
                if ($this->isModalityDateAvailableForThisDate($modalityId, $searchDate)) {
                    if ($modality->slot_type === 'minutes') {
                        // Check if there are available time slots
                        $workingHours = $this->getModalityWorkingHours($modalityId, $searchDate->format('Y-m-d'));
                        $bookedSlots = $this->getBookedSlots($modalityId, $searchDate->format('Y-m-d'));
                        $availableSlots = array_diff($workingHours, $bookedSlots);

                        if (!empty($availableSlots)) {
                            $nextAvailableDate = [
                                'date' => $searchDate->format('Y-m-d'),
                                'available_times' => array_values($availableSlots),
                                'slot_type' => 'minutes'
                            ];
                            break;
                        }
                    } else {
                        // For days slot type, return available durations as numbers
                        $availableDurations = [];
                        $maxDuration = $modality->time_slot_duration;

                        // Check each possible duration (1, 2, 3, etc.)
                        for ($dayCount = 1; $dayCount <= $maxDuration; $dayCount++) {
                            // Apply new rules for 'days' slot type
                            if ($searchDate->dayOfWeek === Carbon::FRIDAY) {
                                continue; // Cannot book on Friday
                            }

                            if ($searchDate->dayOfWeek !== Carbon::THURSDAY) {
                                if ($dayCount > 1) {
                                    continue; // Only one day allowed on non-Thursdays
                                }
                            } else { // It's a Thursday
                                if ($dayCount > 3) {
                                    continue; // Max three days on Thursday
                                }
                            }

                            if ($this->isDaysPeriodAvailable($modalityId, $searchDate, $dayCount)) {
                                $availableDurations[] = $dayCount;
                            }
                        }

                        if (!empty($availableDurations)) {
                            $nextAvailableDate = [
                                'date' => $searchDate->format('Y-m-d'),
                                'available_durations' => $availableDurations,
                                'max_duration' => $maxDuration,
                                'slot_type' => 'days',
                                'available_times' => $this->getModalityWorkingHours($modalityId, $searchDate->format('Y-m-d'))
                            ];
                            break;
                        }
                    }
                }

                $searchDate->addDay();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'canceled_appointments' => $canceledAppointments,
                    'normal_appointments' => $nextAvailableDate,
                    'modality_info' => [
                        'slot_type' => $modality->slot_type,
                        'max_duration' => $modality->time_slot_duration
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching available modality appointments: ' . $e->getMessage(), [
                'modality_id' => $modalityId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate remaining days available for a canceled appointment
     */
    private function calculateRemainingCanceledDays($modalityId, $startDate, $originalDuration)
    {
        $bookedDays = 0;
        $startDateCarbon = Carbon::parse($startDate);

        // Check each day in the original duration to see if it's been rebooked
        for ($i = 0; $i < $originalDuration; $i++) {
            $checkDate = $startDateCarbon->copy()->addDays($i);

            // Check if this specific day has been rebooked (non-canceled appointment exists)
            $hasActiveBooking = MoalityAppointments::where('modality_id', $modalityId)
                ->whereDate('appointment_date', $checkDate->format('Y-m-d'))
                ->whereNotIn('status', $this->excludedStatuses)
                ->exists();

            if ($hasActiveBooking) {
                $bookedDays++;
            }
        }

        return $originalDuration - $bookedDays;
    }


    // ...existing code...
    public function changeModalityAppointmentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:' . implode(',', array_column(AppointmentSatatusEnum::cases(), 'value')),
            'reason' => 'nullable|string'
        ]);

        $modalityAppointment = MoalityAppointments::findOrFail($id);
        $modalityAppointment->status = $validated['status'];

        if ($validated['status'] == AppointmentSatatusEnum::CANCELED->value) {
            $modalityAppointment->reason = $validated['reason'] ?? '--';
            $modalityAppointment->canceled_by = Auth::id();
            $modalityAppointment->canceled_at = Carbon::now();
        } else {
            $modalityAppointment->reason = '--';
            $modalityAppointment->canceled_by = null;
            $modalityAppointment->canceled_at = null;
        }

        $modalityAppointment->save();

        return response()->json([
            'message' => 'Modality appointment status updated successfully.',
            'modality_appointment' => new ModalityAppointmentResource($modalityAppointment),
        ]);
    }

    public function destroy($modalityAppointmentId)
    {
        $modalityAppointment = MoalityAppointments::findOrFail($modalityAppointmentId);
        $modalityAppointment->delete();

        return response()->json([
            'message' => 'Modality appointment deleted successfully.'
        ]);
    }
  

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'patient_first_name' => 'sometimes|required|string|max:255', // Renamed to patient_first_name for clarity
            'patient_last_name' => 'sometimes|required|string|max:255',  // Renamed to patient_last_name for clarity
            'phone' => 'sometimes|required|string|max:20',
            'modality_id' => 'sometimes|required|exists:modalities,id',
            'appointment_date' => 'sometimes|required|date_format:Y-m-d',
            'appointment_time' => 'nullable', // Time can be null for 'days' type
            'description' => 'nullable|string|max:1000',
            'duration_days' => 'nullable|integer|min:1', // New field for 'days' slot_type
        ]);

        $modalityAppointment = MoalityAppointments::findOrFail($id);
        $modalityId = $validated['modality_id'] ?? $modalityAppointment->modality_id;
        $appointmentDate = Carbon::parse($validated['appointment_date'] ?? $modalityAppointment->appointment_date);
        $appointmentTime = $validated['appointment_time'] ?? $modalityAppointment->appointment_time?->format('H:i'); // Handle null time
        $endDate = null;

        $modality = Modality::select(['id', 'slot_type', 'time_slot_duration'])->findOrFail($modalityId);

        if ($modality->slot_type === 'days') {
            $durationDays = $validated['duration_days'] ?? 1;

            // Apply specific rules for 'days' slot_type
            if ($appointmentDate->dayOfWeek === Carbon::FRIDAY) {
                return response()->json([
                    'message' => 'Booking on Friday is not allowed for this modality type.',
                    'errors' => ['appointment_date' => ['You cannot book on a Friday.']]
                ], 422);
            }

            if ($appointmentDate->dayOfWeek !== Carbon::THURSDAY) {
                if ($durationDays > 1) {
                    return response()->json([
                        'message' => 'You can only book one day at a time for this modality type, except on Thursdays.',
                        'errors' => ['duration_days' => ['You cannot book more than one day.']]
                    ], 422);
                }
            } else { // It's a Thursday
                if ($durationDays > 3) {
                    return response()->json([
                        'message' => 'On Thursdays, you can book a maximum of three days.',
                        'errors' => ['duration_days' => ['You can book a maximum of three days on Thursday.']]
                    ], 422);
                }
            }

            if (empty($validated['duration_days'])) {
                return response()->json([
                    'message' => 'Duration in days is required for this modality type.',
                    'errors' => ['duration_days' => ['The duration in days is required.']]
                ], 422);
            }
            if ($validated['duration_days'] > $modality->time_slot_duration) {
                return response()->json([
                    'message' => "Maximum booking duration for this modality is {$modality->time_slot_duration} day(s).",
                    'errors' => ['duration_days' => ["Cannot book for more than {$modality->time_slot_duration} day(s)."]]
                ], 422);
            }
            $endDate = $appointmentDate->copy()->addDays($validated['duration_days'] - 1)->format('Y-m-d');

            for ($i = 0; $i < $validated['duration_days']; $i++) {
                $currentDay = $appointmentDate->copy()->addDays($i);
                if ($currentDay->dayOfWeek === Carbon::FRIDAY) {
                    return response()->json([
                        'message' => 'Booking cannot include Fridays for this modality type.',
                        'errors' => ['appointment_date' => ['The booking period cannot include a Friday.']]
                    ], 422);
                }
            }
        }

        // Check if the time slot/period is already booked, excluding the current appointment
        if (
            !MoalityAppointments::isSlotAvailableForUpdate(
                $modalityId,
                $appointmentDate->format('Y-m-d'), // Pass formatted date
                $appointmentTime,
                $this->excludedStatuses,
                $modalityAppointment->id,
                $endDate // Pass endDate for 'days' slot_type check
            )
        ) {
            return response()->json([
                'message' => 'This time slot or period is already booked.',
                'errors' => ['appointment_time' => ['The selected time slot/period is no longer available.']]
            ], 422);
        }

        $patient = Patient::findOrFail($modalityAppointment->patient_id);
        $patient->update([
            'Firstname' => $validated['patient_first_name'] ?? $patient->Firstname,
            'Lastname' => $validated['patient_last_name'] ?? $patient->Lastname,
            'phone' => $validated['phone'] ?? $patient->phone,
        ]);

        $modalityAppointment->update([
            'modality_id' => $modalityId,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'appointment_time' => $appointmentTime,
            'status' => AppointmentSatatusEnum::SCHEDULED->value,
            'updated_by' => Auth::id(),
            'notes' => $validated['description'] ?? $modalityAppointment->notes,
        ]);

        return new ModalityAppointmentResource($modalityAppointment);
    }
}