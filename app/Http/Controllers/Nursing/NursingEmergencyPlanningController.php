<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Requests\NursingEmergencyPlanningRequest;
use App\Http\Resources\NursingEmergencyPlanningResource;
use App\Models\CONFIGURATION\Service;
use App\Models\NursingEmergencyPlanning;
use App\Models\User;
use App\Services\NursingEmergencyPlanningService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NursingEmergencyPlanningController extends Controller
{
    public function __construct(protected NursingEmergencyPlanningService $planningService) {}

    public function index(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $serviceId = $request->get('service_id');

        $plannings = NursingEmergencyPlanning::with(['nurse', 'service'])
            ->forMonthYear($month, $year)
            ->active()
            ->when($serviceId, fn ($query) => $query->where('service_id', $serviceId))
            ->orderBy('planning_date')
            ->orderBy('shift_start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => NursingEmergencyPlanningResource::collection($plannings),
        ]);
    }

    public function store(NursingEmergencyPlanningRequest $request): JsonResponse
    {
        try {
            $planning = $this->planningService->createPlanning($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nursing planning created successfully',
                'data' => new NursingEmergencyPlanningResource($planning->load(['nurse', 'service'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating planning: '.$e->getMessage(),
            ], 422);
        }
    }

    public function show(NursingEmergencyPlanning $planning): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new NursingEmergencyPlanningResource($planning->load(['nurse', 'service'])),
        ]);
    }

    public function update(NursingEmergencyPlanningRequest $request, NursingEmergencyPlanning $planning): JsonResponse
    {
        try {
            $updatedPlanning = $this->planningService->updatePlanning($planning, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nursing planning updated successfully',
                'data' => new NursingEmergencyPlanningResource($updatedPlanning->load(['nurse', 'service'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating planning: '.$e->getMessage(),
            ], 422);
        }
    }

    public function destroy(NursingEmergencyPlanning $planning): JsonResponse
    {
        try {
            $planning->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nursing planning deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting planning: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getNurses(): JsonResponse
    {
        $nurses = User::where('role', 'nurse')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $nurses->map(function (User $nurse) {
            $displayName = $nurse->name
                ?? trim(($nurse->nom ?? '').' '.($nurse->prenom ?? ''))
                ?: 'Unknown Nurse';

            return [
                'id' => $nurse->id,
                'name' => $displayName,
                'email' => $nurse->email,
                'is_active' => $nurse->is_active,
                'avatar' => $nurse->avatar,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    public function getServices(): JsonResponse
    {
        $services = Service::select('id', 'name', 'description')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name ?? 'Unnamed Service',
                'description' => $service->description,
            ]);

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function getMonthlyOverview(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $overview = $this->planningService->getMonthlyOverview($month, $year);

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    public function checkConflicts(Request $request): JsonResponse
    {
        $conflicts = $this->planningService->checkScheduleConflicts(
            $request->get('nurse_id'),
            $request->get('planning_date'),
            $request->get('shift_start_time'),
            $request->get('shift_end_time'),
            $request->get('exclude_id')
        );

        return response()->json([
            'success' => true,
            'has_conflicts' => $conflicts->count() > 0,
            'conflicts' => NursingEmergencyPlanningResource::collection($conflicts),
        ]);
    }

    public function getNextAvailableTime(Request $request): JsonResponse
    {
        if ($request->boolean('smart')) {
            $suggestion = $this->planningService->getSmartNextAvailableTime(
                $request->get('planning_date')
            );

            return response()->json([
                'success' => true,
                'suggestion' => $suggestion,
            ]);
        }

        $nextStartTime = $this->planningService->getNextAvailableStartTime(
            $request->get('planning_date')
        );

        return response()->json([
            'success' => true,
            'suggested_start_time' => $nextStartTime,
        ]);
    }

    public function copyMonthPlanning(Request $request): JsonResponse
    {
        $request->validate([
            'fromMonth' => 'required|integer|between:1,12',
            'fromYear' => 'required|integer|min:2020',
            'toMonth' => 'required|integer|between:1,12',
            'toYear' => 'required|integer|min:2020',
        ]);

        try {
            $fromMonth = $request->get('fromMonth');
            $fromYear = $request->get('fromYear');
            $toMonth = $request->get('toMonth');
            $toYear = $request->get('toYear');

            $sourcePlannings = NursingEmergencyPlanning::with(['nurse', 'service'])
                ->forMonthYear($fromMonth, $fromYear)
                ->active()
                ->get();

            if ($sourcePlannings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No planning found for source month {$fromMonth}/{$fromYear}.",
                ], 404);
            }

            $fromDate = Carbon::createFromDate($fromYear, $fromMonth, 1);
            $toDate = Carbon::createFromDate($toYear, $toMonth, 1);
            $daysDiff = $fromDate->diffInDays($toDate, false);

            $copiedPlannings = [];

            foreach ($sourcePlannings as $planning) {
                $newPlanningDate = Carbon::parse($planning->planning_date)->addDays($daysDiff);

                if ($newPlanningDate->month === (int) $toMonth && $newPlanningDate->year === (int) $toYear) {
                    $newPlanning = NursingEmergencyPlanning::create([
                        'nurse_id' => $planning->nurse_id,
                        'service_id' => $planning->service_id,
                        'month' => $toMonth,
                        'year' => $toYear,
                        'planning_date' => $newPlanningDate->toDateString(),
                        'shift_start_time' => $planning->shift_start_time,
                        'shift_end_time' => $planning->shift_end_time,
                        'shift_type' => $planning->shift_type,
                        'notes' => $planning->notes,
                        'is_active' => true,
                    ]);

                    $copiedPlannings[] = new NursingEmergencyPlanningResource(
                        $newPlanning->load(['nurse', 'service'])
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully copied '.count($copiedPlannings)." plannings from {$fromMonth}/{$fromYear} to {$toMonth}/{$toYear}",
                'count' => count($copiedPlannings),
                'data' => $copiedPlannings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error copying planning: '.$e->getMessage(),
            ], 422);
        }
    }

    public function generatePrintReport(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $serviceId = $request->get('service_id');

        try {
            $plannings = NursingEmergencyPlanning::with(['nurse', 'service'])
                ->forMonthYear($month, $year)
                ->active()
                ->when($serviceId, fn ($query) => $query->where('service_id', $serviceId))
                ->orderBy('planning_date')
                ->orderBy('shift_start_time')
                ->get();

            $planningsByDate = $plannings->groupBy('planning_date');

            $stats = [
                'total_shifts' => $plannings->count(),
                'total_nurses' => $plannings->pluck('nurse_id')->unique()->count(),
                'total_services' => $plannings->pluck('service_id')->unique()->count(),
                'day_shifts' => $plannings->where('shift_type', 'day')->count(),
                'night_shifts' => $plannings->where('shift_type', 'night')->count(),
                'emergency_shifts' => $plannings->where('shift_type', 'emergency')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
                    'plannings' => NursingEmergencyPlanningResource::collection($plannings),
                    'plannings_by_date' => $planningsByDate->map(
                        fn ($dayPlannings) => NursingEmergencyPlanningResource::collection($dayPlannings)
                    ),
                    'statistics' => $stats,
                    'generated_at' => now()->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: '.$e->getMessage(),
            ], 422);
        }
    }

    public function getDayOverview(Request $request): JsonResponse
    {
        $planningDate = $request->get('planning_date');

        $plannings = NursingEmergencyPlanning::with(['nurse', 'service'])
            ->where('planning_date', $planningDate)
            ->where('is_active', true)
            ->orderBy('shift_start_time')
            ->get();

        $overview = [
            'shifts' => NursingEmergencyPlanningResource::collection($plannings),
            'total_shifts' => $plannings->count(),
            'coverage_hours' => $this->calculateCoverageHours($plannings),
            'next_available_slots' => $this->getAvailableTimeSlots($planningDate, $plannings),
        ];

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    private function calculateCoverageHours($plannings): float
    {
        $totalHours = 0;
        foreach ($plannings as $planning) {
            $start = Carbon::parse($planning->shift_start_time);
            $end = Carbon::parse($planning->shift_end_time);

            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $totalHours += $start->diffInHours($end, false);
        }

        return $totalHours;
    }

    private function getAvailableTimeSlots(string $date, $existingPlannings): array
    {
        $slots = [];
        $standardSlots = [
            ['start' => '06:00', 'end' => '14:00', 'type' => 'day'],
            ['start' => '14:00', 'end' => '22:00', 'type' => 'evening'],
            ['start' => '22:00', 'end' => '06:00', 'type' => 'night'],
        ];

        foreach ($standardSlots as $slot) {
            $isOccupied = $existingPlannings->contains(function ($planning) use ($slot) {
                $planningStart = Carbon::parse($planning->shift_start_time);
                $planningEnd = Carbon::parse($planning->shift_end_time);
                $slotStart = Carbon::parse($slot['start']);
                $slotEnd = Carbon::parse($slot['end']);

                if ($slotEnd->lessThan($slotStart)) {
                    $slotEnd->addDay();
                }

                return $planningStart->lessThan($slotEnd) && $planningEnd->greaterThan($slotStart);
            });

            if (! $isOccupied) {
                $slots[] = [
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'shift_type' => $slot['type'],
                    'is_available' => true,
                ];
            }
        }

        return $slots;
    }
}
