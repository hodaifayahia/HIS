<?php

namespace App\Http\Controllers\CONFIGURATION;

use App\Http\Controllers\Controller;
use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalitySchedule;
use App\Models\CONFIGURATION\ModalityAvailableMonth;
use App\Models\CONFIGURATION\AppointmentModalityForce;
use App\Models\CONFIGURATION\ModalityType;
use App\Models\Specialization;
use Illuminate\Http\Request;
use App\Http\Resources\CONFIGURATION\ModalityResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\DayOfWeekEnum; // Assuming you have a similar Enum for days
use App\AppointmentSatatusEnum; // Assuming you have a similar Enum for appointment statuses

// Import the new classes
use App\Http\Requests\CONFIGURATION\StoreModalityRequest;
use App\Http\Requests\CONFIGURATION\UpdateModalityRequest;
use App\Services\CONFIGURATION\ModalityService;

class ModalityController extends Controller
{
    protected $modalityService;

    public function __construct(ModalityService $modalityService)
    {
        $this->modalityService = $modalityService;
    }

    /**
     * Display a listing of the modalities with search and filter capabilities.
     * Mimics DoctorController's index logic, including is_active filtering.
     */
    public function index(Request $request)
    {
        $filter = $request->query('query');
        $modalityId = $request->query('modality_id');
        $modalityTypeId = $request->query('modality_type_id');
        $operationalStatus = $request->query('operational_status');
        $specialization_id = $request->query('specialization_id');

        $modalitiesQuery = Modality::with([
            'modalityType:id,name',
            'specialization:id,name',
            'schedules',
            'availableMonths',
            'appointmentModalityForce'
        ]);

        // Mimic DoctorController's role-based filtering for 'is_active'
        $user = Auth::user();
        if ($user && in_array($user->role, ['admin', 'SuperAdmin'])) {
            // Admin/SuperAdmin sees all modalities
            // No specific 'user' relationship to filter on for Modality, so no whereHas here
        } else {
            // Other users only see active modalities
            $modalitiesQuery->where('is_active', true);
        }

        if ($filter) {
            $modalitiesQuery->where(function($query) use ($filter) {
                $query->where('name', 'like', "%{$filter}%")
                      ->orWhere('internal_code', 'like', "%{$filter}%");
            });
        }
        if ($specialization_id) {
            $modalitiesQuery->where('specialization_id', $specialization_id);
        }

        if ($modalityId) {
            $modalitiesQuery->where('id', $modalityId);
        }

        if ($modalityTypeId) {
            $modalitiesQuery->where('modality_type_id', $modalityTypeId);
        }

        if ($operationalStatus) {
            $modalitiesQuery->where('operational_status', $operationalStatus);
        }

        $modalities = $modalitiesQuery->paginate(30);

        return ModalityResource::collection($modalities);
    }

    /**
     * Get specific modality by ID.
     * Mimics DoctorController's getDoctor method.
     */
    public function getModality(Request $request, $id = null)
    {
        if ($id) {
            $modality = Modality::with(['modalityType', 'specialization', 'schedules', 'availableMonths',  ])
                                 ->find($id);

            if (!$modality) {
                return response()->json(['message' => 'Modality not found'], 404);
            }

            return new ModalityResource($modality);
        }

        // If no ID, return paginated list (handled by index, but keeping for mimicry)
        return $this->index($request);
    }

    /**
     * Display the specified modality.
     */
    public function show($id)
    {
        try {
            $modality = Modality::with([
                'modalityType:id,name',
                'specialization:id,name',
                'schedules',
                'availableMonths',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ModalityResource($modality)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Modality not found.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get working dates for modalities for a specific month.
     * Mimics DoctorController's WorkingDates method.
     * This will require you to have an `Appointment` model that can be linked to modalities
     * if appointments are also tied to modalities in your system.
     */
    public function WorkingDates(Request $request)
    {
        try {
            $validated = $request->validate([
                'modalityId' => 'nullable|exists:modalities,id',
                'month' => 'required|date_format:Y-m',
            ]);

            $startDate = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $modalitiesQuery = Modality::query()
                ->select([
                    'modalities.id',
                    'modalities.name as modality_name',
                    'modalities.specialization_id',
                    'specializations.name as specialization_name'
                ])
                ->join('specializations', 'modalities.specialization_id', '=', 'specializations.id');

            if (isset($validated['modalityId'])) {
                $modalitiesQuery->where('modalities.id', $validated['modalityId']);
            }

            $modalities = $modalitiesQuery->get();

            if ($modalities->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'month' => $validated['month'],
                    'total_modalities' => 0
                ]);
            }

            $modalityIds = $modalities->pluck('id')->toArray();

            // Get active schedules for modalities
            $schedules = DB::table('modality_schedules') // Use your modality schedules table
                ->select(['modality_id', 'date', 'day_of_week', 'is_active'])
                ->where('is_active', true)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])
                      ->orWhereNull('date');
                })
                ->whereIn('modality_id', $modalityIds)
                ->get();

            // Assuming you have an Appointment model that links to modalities
            // If not, you'll need to adjust this part or remove it.
            $appointments = DB::table('appointments') // Assuming `appointments` table has a `modality_id`
                ->select(['modality_id', 'appointment_date'])
                ->whereIn('modality_id', $modalityIds)
                ->whereBetween('appointment_date', [$startDate, $endDate])
                ->where('status', '!=', AppointmentSatatusEnum::CANCELED->value) // Adjust if no Enum exists for Modalities
                ->whereNull('deleted_at')
                ->distinct()
                ->get();

            // Get excluded dates (you might need a separate table for modality excluded dates)
            // For now, mirroring doctor controller, assuming `excluded_dates` also applies to modalities
            // or you create a new table like `modality_excluded_dates`
            $excludedDates = DB::table('excluded_dates') // Assuming `excluded_dates` table can store `modality_id`
                ->where(function ($query) use ($modalityIds) {
                    $query->whereIn('modality_id', $modalityIds)
                          ->where('exclusionType', 'complete') // Assuming this field exists
                          ->orWhereNull('modality_id'); // Global exclusions
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

            $result = $modalities->map(function ($modality) use ($schedules, $appointments, $excludedDates, $startDate, $endDate) {
                if (!$modality->modality_name || !$modality->specialization_name) {
                    return null;
                }

                $modalityExcludedDates = $excludedDates->filter(function ($excludedDate) use ($modality) {
                    return $excludedDate->modality_id === $modality->id || is_null($excludedDate->modality_id);
                })->values();

                $modalitySchedules = $schedules->where('modality_id', $modality->id);
                $modalityAppointments = $appointments->where('modality_id', $modality->id);

                $workingDates = $this->calculateWorkingDatesOptimized(
                    $modality->id,
                    $modalitySchedules,
                    $modalityAppointments,
                    $modalityExcludedDates,
                    $startDate,
                    $endDate
                );

                return [
                    'id' => $modality->id,
                    'name' => $modality->modality_name,
                    'specialization' => $modality->specialization_name,
                    'excludedDates' => $modalityExcludedDates,
                    'working_dates' => array_values(array_unique($workingDates)),
                ];
            })->filter();

            return response()->json([
                'data' => $result,
                'month' => $validated['month'],
                'total_modalities' => $result->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in WorkingDates (ModalityController)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error fetching working dates for modalities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to calculate working dates, adapted for modalities.
     * Mimics DoctorController's calculateWorkingDatesOptimized.
     */
    private function calculateWorkingDatesOptimized($modalityId, $schedules, $appointments, $excludedDates, $startDate, $endDate)
    {
        $workingDates = [];
        $currentDate = $startDate->copy();

        $modalityExcludedDates = $excludedDates->filter(function ($date) use ($modalityId) {
            return $date->modality_id === $modalityId || $date->modality_id === null;
        });

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            $isExcluded = $modalityExcludedDates->contains(function ($excludedDate) use ($currentDate) {
                $endDate = $excludedDate->end_date ?? $excludedDate->start_date;
                return $currentDate->between($excludedDate->start_date, $endDate);
            });

            if (!$isExcluded) {
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

        $appointmentDates = $appointments
            ->where('modality_id', $modalityId)
            ->map(fn ($appointment) => Carbon::parse($appointment->appointment_date)->format('Y-m-d'))
            ->toArray();

        return array_values(array_unique(array_merge($workingDates, $appointmentDates)));
    }

    /**
     * Store a newly created modality in storage.
     */
    public function store(StoreModalityRequest $request)
    {
        try {
            $modality = $this->modalityService->createModality($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Modality and associated data created successfully!',
                'data' => new ModalityResource($modality->fresh(['modalityType', 'specialization', 'schedules', 'availableMonths',  ]))
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating modality: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating modality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified modality in storage.
     */
    public function update(UpdateModalityRequest $request, Modality $modality)
    {
        try {
            $modality = $this->modalityService->updateModality($modality, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Modality and associated data updated successfully!',
                'data' => new ModalityResource($modality->fresh(['modalityType', 'specialization', 'schedules', 'availableMonths',  ]))
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating modality: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating modality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified modality from storage.
     */
    public function destroy(Modality $modality)
    {
        try {
            $this->modalityService->deleteModality($modality);

            return response()->json(['message' => 'Modality deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error deleting modality: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error deleting modality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete modalities.
     */
    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:modalities,id',
            ]);

            $count = $this->modalityService->bulkDeleteModalities($request->ids);

            return response()->json([
                'message' => "$count modalities deleted successfully"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error bulk deleting modalities: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error deleting modalities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get modalities by specialization ID.
     */
    public function getModalitiesBySpecialization($specializationId)
    {
        $modalities = Modality::where('specialization_id', $specializationId)
            ->with(['modalityType', 'specialization', 'schedules', 'availableMonths'])
            ->get();

        return ModalityResource::collection($modalities);
    }

    /**
     * Search modalities.
     */
    public function search(Request $request)
    {
        $searchTerm = $request->query('query');

        if (empty($searchTerm)) {
            return ModalityResource::collection(
                Modality::with(['modalityType', 'specialization'])
                    ->orderBy('created_at', 'desc')
                    ->paginate()
            );
        }

        $modalities = Modality::where(function ($query) use ($searchTerm) {
            $query->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('internal_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('dicom_ae_title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('ip_address', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('modalityType', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('specialization', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  });
        })
        ->with(['modalityType', 'specialization'])
        ->orderBy('created_at', 'desc')
        ->paginate();

        return ModalityResource::collection($modalities);
    }
}