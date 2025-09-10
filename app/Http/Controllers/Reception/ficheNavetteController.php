<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\ReceptionRequest;
use App\Http\Requests\Reception\ficheNavetteItemRequest;
use App\Http\Resources\Reception\ficheNavetteResource;
use App\Http\Resources\Reception\ficheNavetteItemResource;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\Service;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Doctor;
use App\Services\Reception\ReceptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ficheNavetteController extends Controller
{
    protected $receptionService;

    public function __construct(ReceptionService $receptionService)
    {
        $this->receptionService = $receptionService;
    }

    public function index(Request $request)
    {
        $query = FicheNavette::with(['creator', 'patient', 'items.prestation']);

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference', 'like', $searchTerm)
                  ->orWhereHas('patient', function ($patientQuery) use ($searchTerm) {
                      $patientQuery->where('Firstname', 'like', $searchTerm)
                                   ->orWhere('Lastname', 'like', $searchTerm);
                  });
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Apply date filter
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
                    'internal_code' => $dep->internal_code,
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
}
