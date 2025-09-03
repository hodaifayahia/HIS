<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ReceptionRequest;
use App\Http\Requests\Reception\ficheNavetteItemRequest;
use App\Http\Resources\Reception\ficheNavetteResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Doctor;
use App\Services\Reception\ReceptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Reception\FichePatientSummaryResource;
use Carbon\Carbon;
use DB;

class ficheNavetteController extends Controller
{
    protected $receptionService;

    public function __construct(ReceptionService $receptionService)
    {
        $this->receptionService = $receptionService;
    }

  public function index(Request $request)
{
    $query = FicheNavette::with([
        'creator:id,name',
        'patient:id,Firstname,Lastname,balance',
        'items.prestation:id,name,internal_code,public_price,specialization_id',
        'items.prestation.specialization:id,name',
        'items.dependencies:id,dependency_type,notes,payment_status,dependent_prestation_id,is_package',
        'items.dependencies.dependencyPrestation:id,name,internal_code,public_price',
    ])
    ->select([
        'id', 'patient_id', 'creator_id', 'status', 'fiche_date',
        'total_amount', 'created_at', 'updated_at'
    ]);

    if ($request->has('search') && $request->search) {
        $searchTerm = '%' . $request->search . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->where('reference', 'like', $searchTerm)
              ->orWhereHas('patient', fn($q) => $q->where('Firstname', 'like', $searchTerm)
                                   ->orWhere('Lastname', 'like', $searchTerm));
        });
    }

    if ($request->has('status') && $request->status !== '') {
        $query->where('status', $request->status);
    }

    if ($request->has('date') && $request->date) {
        $query->whereDate('created_at', $request->date);
    }

    $ficheNavettes = $query->orderBy('created_at', 'desc')->paginate(15);

    return ficheNavetteResource::collection($ficheNavettes);
}

     public function store(Request $request)
    {
        $validatedData = $request->validate([
            'patient_id' => 'required',
            'notes' => 'nullable|string'
        ]);

        try {
            // Simple fiche creation - no items initially
            $ficheNavette = ficheNavette::create([
                'patient_id' => $validatedData['patient_id'],
                'creator_id' => Auth::id(),
                'status' => 'pending',
                'fiche_date' => now(),
                'total_amount' => 0,
                'creator_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fiche Navette created successfully',
                'data' => new ficheNavetteResource($ficheNavette->load(['patient', 'creator']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Fiche Navette',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $ficheNavette = FicheNavette::with(['creator', 'patient', 'items.prestation'])->findOrFail($id);
        return new ficheNavetteResource($ficheNavette);
    }

    /**
     * Return prestations for a fiche filtered to the authenticated user's specializations.
     * Frontend should call this when opening Details to ensure only relevant prestations are shown.
     */
     public function getPrestationsForFicheByAuthenticatedUser($ficheId, Request $request = null)
    {
        $request = $request ?? request();
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        try {
            $user = User::with(['activeSpecializations.specialization', 'specializations', 'specialization'])->find($user->id) ?? $user;
        } catch (\Throwable $e) {
            // ignore and continue
        }

        // collect specialization ids from possible relation shapes
        $specIds = [];
        if (isset($user->activeSpecializations) && $user->activeSpecializations) {
            try {
                $specIds = collect($user->activeSpecializations)->map(function ($s) {
                    if (isset($s->specialization_id)) return $s->specialization_id;
                    if (isset($s->specialization) && isset($s->specialization->id)) return $s->specialization->id;
                    if (isset($s->id)) return $s->id;
                    return null;
                })->filter()->unique()->values()->toArray();
            } catch (\Throwable $ex) {
                $specIds = [];
            }
        }

        if (empty($specIds)) {
            if (isset($user->specialization_id) && $user->specialization_id) {
                $specIds = [(int)$user->specialization_id];
            } elseif (isset($user->specialization_ids) && is_array($user->specialization_ids) && !empty($user->specialization_ids)) {
                $specIds = array_map('intval', $user->specialization_ids);
            } elseif (isset($user->specializations) && is_array($user->specializations) && !empty($user->specializations)) {
                $specIds = array_values(array_filter(array_map(function ($s) {
                    if (is_array($s) && isset($s['id'])) return (int)$s['id'];
                    if (is_object($s) && isset($s->id)) return (int)$s->id;
                    return null;
                }, $user->specializations)));
            }
        }

        // Load the fiche and its items (with prestation + specialization)
        $fiche = FicheNavette::with(['items.prestation.specialization', 'patient'])->find($ficheId);

        if (!$fiche) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Fiche not found'
            ], 200);
        }

        // If no spec ids, return empty
        if (empty($specIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No specializations found for user'
            ], 200);
        }

        $allItems = $fiche->items ?? collect();

        // Filter fiche items by user specializations
        $filteredItems = $allItems->filter(function ($item) use ($specIds) {
            $prestation = $item->prestation ?? null;
            if (!$prestation) return false;
            $sid = null;
            if (isset($prestation->specialization_id) && $prestation->specialization_id) {
                $sid = (int)$prestation->specialization_id;
            } elseif (isset($prestation->specialization) && isset($prestation->specialization->id)) {
                $sid = (int)$prestation->specialization->id;
            }
            return $sid !== null && in_array($sid, $specIds, true);
        })->values();

        if ($filteredItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No prestations found for this fiche for the authenticated user'
            ], 200);
        }

        // Parent item ids for loading dependencies
        $parentItemIds = $filteredItems->pluck('id')->unique()->values()->toArray();

        // Load dependencies for those parent items (with their prestation and specialization)
        $dependencies = ItemDependency::with(['dependencyPrestation.specialization'])
            ->whereIn('parent_item_id', $parentItemIds)
            ->get();

        // Filter dependencies by specialization
        $filteredDeps = $dependencies->filter(function ($dep) use ($specIds) {
            $pre = $dep->dependencyPrestation ?? null;
            if (!$pre) return false;
            $sid = $pre->specialization_id ?? ($pre->specialization->id ?? null);
            return $sid !== null && in_array((int)$sid, $specIds, true);
        })->values();

        // Build a map of filtered items for quick parent lookup
        $itemMap = $filteredItems->keyBy('id');

        // Map fiche items payload
        $itemsPayload = $filteredItems->map(function ($item) use ($fiche) {
            $prestation = $item->prestation;
            return [
                'type' => 'item',
                'id' => $item->id,
                'fiche_navette_id' => $item->fiche_navette_id,
                'fiche_reference' => $fiche->reference ?? null,
                'fiche_status' => $fiche->status ?? null,
                'patient_name' => $fiche->patient?->Firstname . ' ' . $fiche->patient?->Lastname ?? null,
                'patient_phone' => $fiche->patient?->phone ?? null,
                'fiche_date' => $fiche->fiche_date ?? null,
                'status' => $item->status ?? null,
                'payment_status' => $item->payment_status ?? null,
                'paid_amount' => $item->paid_amount ?? null,
                'remaining_amount' => $item->remaining_amount ?? null,
                'prestation_id' => $prestation->id ?? null,
                'prestation_name' => $prestation->name ?? null,
                'price' => $item->amount ?? $prestation->public_price ?? null,
                'specialization_id' => $prestation->specialization_id ?? ($prestation->specialization->id ?? null),
                'specialization_name' => $prestation->specialization->name ?? null,
                'notes' => $item->notes ?? null,
            ];
        });

        // Map dependency payloads and attach fiche info using parent_item_id -> itemMap
        $depsPayload = $filteredDeps->map(function ($dep) use ($itemMap) {
            $pre = $dep->dependencyPrestation;
            $parent = $itemMap->get($dep->parent_item_id);
            $fiche = $parent?->fiche_navette_id ? FicheNavette::find($parent->fiche_navette_id) : null;
            return [
                'type' => 'dependency',
                'id' => 'dep_' . $dep->id,
                'dependency_id' => $dep->id,
                'parent_item_id' => $dep->parent_item_id,
                'fiche_navette_id' => $parent?->fiche_navette_id ?? null,
                'fiche_reference' => $fiche?->reference ?? null,
                'fiche_status' => $fiche?->status ?? null,
                'patient_name' => $fiche?->patient?->Firstname . ' ' . $fiche?->patient?->Lastname ?? null,
                'patient_phone' => $fiche?->patient?->phone ?? null,
                'fiche_date' => $fiche?->fiche_date ?? null,
                'payment_status' => $dep->payment_status ?? null,
                'status' => $dep->status ?? null,
                'paid_amount' => $dep->paid_amount ?? null,
                'remaining_amount' => $dep->remaining_amount ?? null,
                'prestation_id' => $pre->id ?? null,
                'prestation_name' => $pre->name ?? null,
                'price' => $dep->final_price ?? $dep->base_price ?? $pre->public_price ?? null,
                'specialization_id' => $pre->specialization_id ?? ($pre->specialization->id ?? null),
                'specialization_name' => $pre->specialization->name ?? null,
                'notes' => $dep->notes ?? null
            ];
        });

        $combined = $itemsPayload->merge($depsPayload)->values();

        return response()->json([
            'success' => true,
            'data' => $combined
        ], 200);
    }

    public function update(Request $request, $id)
    {
        // Implementation for updating fiche navette
    }

    public function destroy($id)
    {
        $ficheNavette = FicheNavette::findOrFail($id);
        $ficheNavette->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fiche Navette deleted successfully'
        ]);
    }

    // New methods for Add Items functionality


    /**
     * Return today's fiches and fiches not paid, filtered and paginated.
     * Uses FichePatientSummaryResource for output.
     */
    

    public function getPrestationsByService($serviceId): JsonResponse
    {
        $prestations = Prestation::with(['service', 'specialization', 'modalityType'])
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->get()
            ->map(function ($prestation) {
                return [
                    'id' => $prestation->id,
                    'name' => $prestation->name,
                    'internal_code' => $prestation->internal_code,
                    'description' => $prestation->description,
                    'price' => $prestation->public_price,
                    'duration' => $prestation->default_duration_minutes,
                    'service_id' => $prestation->service_id,
                    'service_name' => $prestation->service->name ?? '',
                    'need_an_appointment' => $prestation->need_an_appointment,
                    'specialization_id' => $prestation->specialization_id,
                    'specialization_name' => $prestation->specialization->name ?? '',
                    'required_prestations_info' => $prestation->required_prestations_info,
                    'type' => 'prestation'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $prestations
        ]);
    }

    public function getPackagesByService($serviceId): JsonResponse
    {
        $packages = PrestationPackage::with(['items.prestation'])
            ->whereHas('items.prestation', function ($query) use ($serviceId) {
                $query->where('service_id', $serviceId);
            })
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'service_id' => $package->items->first()->prestation->service_id ?? null,
                    'prestations' => $package->items->map(function ($item) {
                        return [
                            'id' => $item->prestation->id,
                            'name' => $item->prestation->name,
                            'price' => $item->prestation->public_price,
                            'quantity' => 1
                        ];
                    }),
                    'type' => 'package'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

   public function getPrestationsDependencies(Request $request): JsonResponse
{
    // Get the prestation IDs from the request
    $prestationIds = $request->input('ids', []);

    // If it's a string like "[1,2,3]" decode it
    if (is_string($prestationIds)) {
        $prestationIds = json_decode($prestationIds, true);
    }

    // Guarantee we are working with an array
    if (!is_array($prestationIds) || empty($prestationIds)) {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    $dependencies = [];

    foreach ($prestationIds as $prestationId) {
        $prestation = Prestation::find($prestationId);

        if (!$prestation || empty($prestation->required_prestations_info)) {
            continue;
        }

        // required_prestations_info is stored as JSON in DB
        $dependencyIds = is_array($prestation->required_prestations_info) 
            ? $prestation->required_prestations_info 
            : json_decode($prestation->required_prestations_info, true);

        if (!is_array($dependencyIds) || empty($dependencyIds)) {
            continue;
        }

        $deps = Prestation::whereIn('id', $dependencyIds)
            ->get()
            ->map(function ($dep) use ($prestationId) {
                return [
                    'id' => $dep->id,
                    'name' => $dep->name,
                    'need_an_appointment' => $dep->need_an_appointment,
                    'internal_code' => $dep->public_price,
                    'price' => $dep->public_price,
                    'parent_id' => $prestationId,
                ];
            })
            ->toArray();

        // Merge while preserving numeric keys
        $dependencies = array_merge($dependencies, $deps);
    }

    return response()->json([
        'success' => true,
        'data' => $dependencies,
    ]);
}


    public function searchPrestations(Request $request): JsonResponse
    {
        $query = Prestation::with(['service', 'specialization'])
            ->where('is_active', true);

        // Search by term
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('internal_code', 'like', $searchTerm)
                  ->orWhere('billing_code', 'like', $searchTerm);
            });
        }

        // Filter by services
        if ($request->has('services') && !empty($request->services)) {
            $query->whereIn('service_id', $request->services);
        }

        // Filter by specializations
        if ($request->has('specializations') && !empty($request->specializations)) {
            $query->whereIn('specialization_id', $request->specializations);
        }

        $prestations = $query->paginate($request->input('per_page', 10));

        $formattedPrestations = $prestations->getCollection()->map(function ($prestation) {
            return [
                'id' => $prestation->id,
                'name' => $prestation->name,
                'internal_code' => $prestation->internal_code,
                'description' => $prestation->description,
                'price' => $prestation->public_price,
                'service_id' => $prestation->service_id,
                'service_name' => $prestation->service->name ?? '',
                                    'need_an_appointment' => $prestation->need_an_appointment,

                'specialization_id' => $prestation->specialization_id,
                'required_prestations_info' => $prestation->required_prestations_info ?? [],
                'specialization_name' => $prestation->specialization->name ?? '',
                'default_duration_minutes' => $prestation->default_duration_minutes,
                'selected_doctor_id' => null,
                'doctor_name' => ''
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedPrestations,
            'pagination' => [
                'current_page' => $prestations->currentPage(),
                'per_page' => $prestations->perPage(),
                'total' => $prestations->total(),
                'last_page' => $prestations->lastPage()
            ]
        ]);
    }

    public function getAllSpecializations(): JsonResponse
    {
        $specializations = Specialization::where('is_active', true)
            ->get()
            ->map(function ($specialization) {
                return [
                    'id' => $specialization->id,
                    'name' => $specialization->name
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $specializations
        ]);
    }

    // Existing methods
    public function changeStatus(Request $request, $id)
    {
        // Implementation for changing status
    }

    public function addPrestation(Request $request, $id)
    {
        // Implementation for adding prestation
    }

    public function updatePrestation(Request $request, $ficheNavetteId, $itemId)
    {
        // Implementation for updating prestation
    }

    public function removePrestation($ficheNavetteId, $itemId)
    {
        dd($ficheNavetteId, $itemId);
    }

    public function getPrestationsBySpecialization($specializationId): JsonResponse
    {
        $prestations = Prestation::with(['service', 'specialization', 'modalityType'])
            ->where('specialization_id', $specializationId)
            ->where('is_active', true)
            ->get()
            ->map(function ($prestation) {
                return [
                    'id' => $prestation->id,
                    'name' => $prestation->name,
                    'internal_code' => $prestation->internal_code,
                    'description' => $prestation->description,
                    'price' => $prestation->public_price,
                    'duration' => $prestation->default_duration_minutes,
                    'service_id' => $prestation->service_id,
                                        'need_an_appointment' => $prestation->need_an_appointment,

                    'service_name' => $prestation->service->name ?? '',
                    'specialization_id' => $prestation->specialization_id,
                    'specialization_name' => $prestation->specialization->name ?? '',
                    'required_prestations_info' => $prestation->required_prestations_info,
                    'type' => 'prestation'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $prestations
        ]);
    }

    public function getPackagesBySpecialization($specializationId): JsonResponse
    {
        $packages = PrestationPackage::with(['items.prestation'])
            ->whereHas('items.prestation', function ($query) use ($specializationId) {
                $query->where('specialization_id', $specializationId);
            })
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'specialization_id' => $package->items->first()->prestation->specialization_id ?? null,
                    'prestations' => $package->items->map(function ($item) {
                        return [
                            'id' => $item->prestation->id,
                            'name' => $item->prestation->name,
                            'price' => $item->prestation->public_price,
                            'quantity' => 1
                        ];
                    }),
                    'type' => 'package'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

  public function getDoctorsBySpecialization($specializationId): JsonResponse
{
    // Use Doctor model directly since doctors belong to only one specialization
    $doctors = Doctor::with(['user', 'specialization'])
        ->where('specialization_id', $specializationId)
        ->where('is_active', true)
        ->whereHas('user', function ($query) {
            $query->where('is_active', true);
        })
        ->get()
        ->map(function ($doctor) {
            return [
                'id' => $doctor->user->id, // Use user ID for doctor selection
                'name' => $doctor->user->name,
                'specialization_id' => $doctor->specialization_id,
                'specialization_name' => $doctor->specialization->name ?? '',
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $doctors
    ]);
}

public function getAllPrestations(Request $request)
{
    try {
        $prestations = Prestation::with(['service', 'specialization'])
            ->select([
                'id',
                'name', 
                'internal_code',
                'public_price',
                'need_an_appointment', // IMPORTANT: Include this field
                'service_id',
                'specialization_id',
                'default_duration_minutes',
                'description',
                'required_prestations_info'
            ])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => PrestationResource::collection($prestations)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch prestations',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function getAllPrestationsWithPackages(): JsonResponse
{
    $prestations = Prestation::with(['service', 'specialization', 'packages'])
        ->where('is_active', true)
        ->get()
        ->map(function ($prestation) {
            $packageName = null;
            if ($prestation->packages && $prestation->packages->count() > 0) {
                $packageName = $prestation->packages->first()->name;
            }

            return [
                'id' => $prestation->id,
                'name' => $prestation->name,
                'internal_code' => $prestation->internal_code,
                'description' => $prestation->description,
                'price' => $prestation->public_price,
                'service_id' => $prestation->service_id,
                'service_name' => $prestation->service->name ?? '',
                'specialization_id' => $prestation->specialization_id,
                'specialization_name' => $prestation->specialization->name ?? '',
                'default_duration_minutes' => $prestation->default_duration_minutes,
                'package_name' => $packageName,
                'selected_doctor_id' => null,
                'doctor_name' => '',
                'type' => 'prestation'
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $prestations
    ]);
}
    private function generateReference(): string
    {
        $prefix = 'FN';
        $date = now()->format('Ymd');
        $sequence = FicheNavette::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . sprintf('%04d', $sequence);
    }

    /**
     * Return patient summary: today's fiches + fiches not payed.
     * Supports filters: service_id, patient_name, status, per_page
     */
    public function patientSummary(Request $request)
    {
        $filters = $request->only(['service_id', 'patient_name', 'status', 'per_page']);

        $result = $this->receptionService->getTodayAndUnpaidPatients($filters);

        // If paginated result, use resource collection with pagination meta
        if ($result instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return \App\Http\Resources\Reception\FichePatientSummaryResource::collection($result)
                        ->response()
                        ->setStatusCode(200);
        }

        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\Reception\FichePatientSummaryResource::collection($result)
        ], 200);
    }

public function updatePrestationStatus(Request $request, $prestationId): JsonResponse
{
    $request->validate(['status' => 'required|string|in:pending,confirmed,working,canceled,done']);

    DB::beginTransaction();
    try {
        // support ids like "dep_123" coming from frontend
        if (is_string($prestationId) && str_starts_with($prestationId, 'dep_')) {
            $prestationId = (int) substr($prestationId, 4);
        } else {
            $prestationId = (int) $prestationId;
        }

        // 1) Try to update the fiche navette item
        $item = FicheNavetteItem::with('prestation')->find($prestationId);
        if ($item) {
            $item->status = $request->input('status');
            $item->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'data' => $item
            ]);
        }

        // 2) Try to update an ItemDependency model row
        $dep = ItemDependency::with('dependencyPrestation')->find($prestationId);
        if ($dep) {
            // prefer updating 'status' column if exists, otherwise try 'payment_status'
            $updateColumn = null;
            $table = $dep->getTable();

            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'status')) {
                $updateColumn = 'status';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'payment_status')) {
                $updateColumn = 'payment_status';
            }

            if ($updateColumn) {
                $dep->{$updateColumn} = $request->input('status');
                $dep->save();

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Dependency status updated successfully.',
                    'data' => $dep
                ]);
            }

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Dependency found but no updatable status column.'
            ], 400);
        }

        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Prestation or dependency not found.'
        ], 404);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update status: ' . $e->getMessage()
        ], 500);
    }
}

    public function getPrestationsForTodayAndPendingByAuthenticatedUser(Request $request): JsonResponse
{
    $user = $request->user() ?? Auth::user();
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
    }

    // Ensure we have the user's specialization relations loaded
    try {
        $user = User::with(['activeSpecializations.specialization', 'specializations', 'specialization'])->find($user->id) ?? $user;
    } catch (\Throwable $e) {
        // ignore, continue with whatever shape we have
    }

    // Collect specialization ids (same logic as single fiche method)
    $specIds = [];
    if (isset($user->activeSpecializations) && $user->activeSpecializations) {
        try {
            $specIds = collect($user->activeSpecializations)->map(function ($s) {
                if (isset($s->specialization_id)) return (int)$s->specialization_id;
                if (isset($s->specialization) && isset($s->specialization->id)) return (int)$s->specialization->id;
                if (isset($s->id)) return (int)$s->id;
                return null;
            })->filter()->unique()->values()->toArray();
        } catch (\Throwable $ex) {
            $specIds = [];
        }
    }

    if (empty($specIds)) {
        if (isset($user->specialization_id) && $user->specialization_id) {
            $specIds = [(int)$user->specialization_id];
        } elseif (isset($user->specialization_ids) && is_array($user->specialization_ids) && !empty($user->specialization_ids)) {
            $specIds = array_map('intval', $user->specialization_ids);
        } elseif (isset($user->specializations) && is_array($user->specializations) && !empty($user->specializations)) {
            $specIds = array_values(array_filter(array_map(function ($s) {
                if (is_array($s) && isset($s['id'])) return (int)$s['id'];
                if (is_object($s) && isset($s->id)) return (int)$s->id;
                return null;
            }, $user->specializations)));
        }
    }

    // If the user has no specializations detected, return empty list
    if (empty($specIds)) {
        return response()->json(['success' => true, 'data' => [], 'message' => 'No specializations found for user'], 200);
    }

    // Query fiches: today OR status pending (case-insensitive)
    $today = Carbon::today()->toDateString();
    $fiches = FicheNavette::with(['items.prestation.specialization'])
        ->where(function ($q) use ($today) {
            $q->whereDate('fiche_date', $today)
              ->orWhereRaw('LOWER(status) = ?', ['pending']);
        })
        ->orderBy('created_at', 'desc')
        ->get();

    if ($fiches->isEmpty()) {
        return response()->json(['success' => true, 'data' => [], 'message' => 'No fiches found for today or pending'], 200);
    }

    // Gather all items across selected fiches
    $allItems = $fiches->flatMap(function ($fiche) {
        return ($fiche->items ?? collect())->map(function ($it) use ($fiche) {
            $it->fiche = $fiche; // attach parent fiche for mapping
            return $it;
        });
    });

    if ($allItems->isEmpty()) {
        return response()->json(['success' => true, 'data' => [], 'message' => 'No items found in selected fiches'], 200);
    }

    // Build map of items by id to attach fiche info to dependencies later
    $itemMap = $allItems->keyBy('id');

    // Filter fiche items by user's specialization ids (existing)
    $filteredItems = $allItems->filter(function ($item) use ($specIds) {
        $prestation = $item->prestation ?? null;
        if (!$prestation) return false;
        $sid = null;
        if (isset($prestation->specialization_id) && $prestation->specialization_id) {
            $sid = (int)$prestation->specialization_id;
        } elseif (isset($prestation->specialization) && isset($prestation->specialization->id)) {
            $sid = (int)$prestation->specialization->id;
        }
        return $sid !== null && in_array($sid, $specIds, true);
    });

    // Collect parent item ids to load dependencies
    $parentItemIds = $allItems->pluck('id')->unique()->values()->toArray();

    // Load dependencies for those parent items
    $dependencies = ItemDependency::with(['dependencyPrestation.specialization'])
        ->whereIn('parent_item_id', $parentItemIds)
        ->get();

    // Filter dependencies by specialization (dependencyPrestation)
    $filteredDeps = $dependencies->filter(function ($dep) use ($specIds) {
        $pre = $dep->dependencyPrestation ?? null;
        if (!$pre) return false;
        $sid = $pre->specialization_id ?? ($pre->specialization->id ?? null);
        return $sid !== null && in_array((int)$sid, $specIds, true);
    });

    // Map fiche items payload
    $itemsPayload = $filteredItems->map(function ($item) {
        $prestation = $item->prestation;
        $fiche = $item->fiche ?? null;
        return [
            'type' => 'item',
            'id' => $item->id,
            'fiche_navette_id' => $item->fiche_navette_id,
            'fiche_reference' => $fiche?->reference ?? null,
            'fiche_status' => $fiche?->status ?? null,
            'patient_name' => $fiche?->patient?->Firstname . ' ' . $fiche?->patient?->Lastname ?? null,
            'patient_phone' => $fiche?->patient?->phone ?? null,
            'fiche_date' => $fiche?->fiche_date ?? null,
            'payment_status' => $dep->payment_status ?? null,
            'status' => $item->status ?? null,
             'paid_amount' => $item->paid_amount ?? null,
                'remainig_amount' => $item->remaining_amount ?? null,
            'prestation_id' => $prestation->id ?? null,
            'prestation_name' => $prestation->name ?? null,
            'price' => $item->amount ?? $prestation->public_price ?? null,
            'specialization_id' => $prestation->specialization_id ?? ($prestation->specialization->id ?? null),
            'specialization_name' => $prestation->specialization->name ?? null,
            'payment_status' => $item->payment_status ?? null,
            'notes' => $item->notes ?? null
        ];
    });

    // Map dependency payloads and attach fiche info using parent_item_id -> itemMap
    $depsPayload = $filteredDeps->map(function ($dep) use ($itemMap) {
        $pre = $dep->dependencyPrestation;
        $parent = $itemMap->get($dep->parent_item_id);
        $fiche = $parent->fiche ?? null;
        return [
            'type' => 'dependency',
            'id' => 'dep_' . $dep->id,
            'dependency_id' => $dep->id,
            'parent_item_id' => $dep->parent_item_id,
            'fiche_navette_id' => $parent->fiche_navette_id ?? null,
            'fiche_reference' => $fiche?->reference ?? null,
            'fiche_status' => $fiche?->status ?? null,
            'patient_name' => $fiche?->patient?->Firstname . ' ' . $fiche?->patient?->Lastname ?? null,
            'patient_phone' => $fiche?->patient?->phone ?? null,
            'fiche_date' => $fiche?->fiche_date ?? null,
            'payment_status' => $dep->payment_status ?? null,
            'status' => $item->status ?? null,
             'paid_amount' => $item->paid_amount ?? null,
                'remainig_amount' => $item->remaining_amount ?? null,
            'prestation_id' => $pre->id ?? null,
            'prestation_name' => $pre->name ?? null,
            'price' => $dep->final_price ?? $dep->base_price ?? $pre->public_price ?? null,
            'specialization_id' => $pre->specialization_id ?? ($pre->specialization->id ?? null),
            'specialization_name' => $pre->specialization->name ?? null,
            'payment_status' => $dep->payment_status ?? null,
            'notes' => $dep->notes ?? null
        ];
    });

    // Combine both payloads and return
    $combined = $itemsPayload->merge($depsPayload)->values();

    return response()->json([
        'success' => true,
        'data' => $combined,
    ], 200);
}
}
