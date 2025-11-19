<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\UpdateFicheNavetteRequest;
use App\Http\Resources\PrestationResource;
use App\Http\Resources\Reception\FicheNavetteResource;
use App\Http\Resources\Reception\FichePatientSummaryResource;
use App\Models\Appointment\AppointmentPrestation;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\Doctor;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Specialization;
use App\Models\User;
use App\Services\Reception\ReceptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DB;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'patient:id,Firstname,Lastname,balance,is_faithful',
            // Request underlying DB columns used by the accessor instead of the accessor name
            'patient.user:id,name',
            'items.prestation.service:id,name',
            'items.prestation.specialization:id,name',
            'items.prestation.doctor:id',
            'items.prestation.doctor.user:id,name',
            'items.doctor:id',
            'items.doctor.user:id,name',
            'items.dependencies.dependencyPrestation.doctor:id',
            'items.dependencies.dependencyPrestation.doctor.user:id,name',
        ])
            ->when($request->user(), function ($q) use ($request) {
                // Apply user-based filtering if needed
                return $q;
            });

        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('patient', function ($patientQuery) use ($searchTerm) {
                    $patientQuery->where('Firstname', 'like', "%{$searchTerm}%")
                        ->orWhere('Lastname', 'like', "%{$searchTerm}%");
                })->orWhere('reference', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('date') && $request->date) {
            $query->whereDate('fiche_date', $request->date);
        }

        $ficheNavettes = $query->orderBy('created_at', 'desc')->paginate(15);

        return FicheNavetteResource::collection($ficheNavettes);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'notes' => 'nullable|string',
        ]);

        // try {
            DB::beginTransaction();

            // Create fiche navette
            $ficheNavette = ficheNavette::create([
                'patient_id' => $validatedData['patient_id'],
                'creator_id' => Auth::id(),
                'status' => 'pending',
                'fiche_date' => now(),
                'total_amount' => 0,
            ]);

            // Optionally load prestations from today's appointments
            $appointmentPrestations = AppointmentPrestation::with(['appointment', 'prestation'])
                ->whereHas('appointment', function ($query) use ($validatedData) {
                    $query->where('patient_id', $validatedData['patient_id'])
                        ->whereDate('appointment_date', Carbon::today());
                })
                ->get();
                // dd($appointmentPrestations );
            // Create fiche items from appointment prestations if any exist
            if ($appointmentPrestations->isNotEmpty()) {
                foreach ($appointmentPrestations as $appPrestation) {
                    if ($appPrestation->prestation) {
                        $ficheNavette->items()->create([
                            'prestation_id' => $appPrestation->prestation_id,
                            'patient_id' => $validatedData['patient_id'],
                            'base_price' => $appPrestation->prestation->price_with_vat ?? 0,
                            'final_price' => $appPrestation->prestation->price_with_vat ?? 0,
                            'status' => 'pending',
                            'payment_status' => 'unpaid',
                        ]);
                    }
                }

                // Recalculate total amount
                $totalAmount = $ficheNavette->items()->sum('final_price');
                $ficheNavette->update(['total_amount' => $totalAmount]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fiche Navette created successfully',
                'data' => new FicheNavetteResource($ficheNavette->load(['patient', 'creator', 'items.prestation'])),
            ], 201);

        // } catch (\Exception $e) {
        //     DB::rollBack();

        //     \Log::error('Failed to create Fiche Navette', [
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString(),
        //         'request' => $request->all(),
        //     ]);

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to create Fiche Navette',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }
    }

    public function show($id)
    {
        $ficheNavette = FicheNavette::with(['creator', 'patient', 'items.prestation'])->findOrFail($id);

        return new FicheNavetteResource($ficheNavette);
    }

    /**
     * Return all prestations for a fiche without filtering by user specializations.
     * This ensures all items are displayed when any specialization is selected.
     */
    public function getPrestationsForFicheByAuthenticatedUser($ficheId, ?Request $request = null)
    {
        $request = $request ?? request();
        $user = $request->user() ?? Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Load the fiche and its items (with prestation + specialization + doctor)
        $fiche = FicheNavette::with(['items.prestation.specialization', 'items.prestation.doctor', 'patient'])->find($ficheId);

        if (! $fiche) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Fiche not found',
            ], 200);
        }

        $allItems = $fiche->items ?? collect();

        // Return all items without filtering by user specializations
        $filteredItems = $allItems->filter(function ($item) {
            $prestation = $item->prestation ?? null;
            return $prestation !== null;
        })->values();

        if ($filteredItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No prestations found for this fiche',
            ], 200);
        }

        // Parent item ids for loading dependencies
        $parentItemIds = $filteredItems->pluck('id')->unique()->values()->toArray();

        // Load dependencies for those parent items (with their prestation, specialization, and doctor)
        $dependencies = ItemDependency::with(['dependencyPrestation.specialization', 'dependencyPrestation.doctor'])
            ->whereIn('parent_item_id', $parentItemIds)
            ->get();

        // Return all dependencies without filtering by specialization
        $filteredDeps = $dependencies->filter(function ($dep) {
            $pre = $dep->dependencyPrestation ?? null;
            return $pre !== null;
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
                'patient_name' => $fiche->patient?->Firstname.' '.$fiche->patient?->Lastname ?? null,
                'patient_phone' => $fiche->patient?->phone ?? null,
                'fiche_date' => $fiche->fiche_date ?? null,
                'status' => $item->status ?? null,
                'payment_status' => $item->payment_status ?? null,
                'paid_amount' => $item->paid_amount ?? null,
                'remaining_amount' => $item->remaining_amount ?? null,
                'prestation_id' => $prestation->id ?? null,
                'prestation_name' => $prestation->name ?? null,
                'price' => $item->amount ?? $prestation->price_with_vat_and_consumables_variant ?? null,
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
                'id' => 'dep_'.$dep->id,
                'dependency_id' => $dep->id,
                'parent_item_id' => $dep->parent_item_id,
                'fiche_navette_id' => $parent?->fiche_navette_id ?? null,
                'fiche_reference' => $fiche?->reference ?? null,
                'fiche_status' => $fiche?->status ?? null,
                'patient_name' => $fiche?->patient?->Firstname.' '.$fiche?->patient?->Lastname ?? null,
                'patient_phone' => $fiche?->patient?->phone ?? null,
                'fiche_date' => $fiche?->fiche_date ?? null,
                'payment_status' => $dep->payment_status ?? null,
                'status' => $dep->status ?? null,
                'paid_amount' => $dep->paid_amount ?? null,
                'remaining_amount' => $dep->remaining_amount ?? null,
                'prestation_id' => $pre->id ?? null,
                'prestation_name' => $pre->name ?? null,
                'price' => $dep->final_price ?? $dep->base_price ?? $pre->price_with_vat_and_consumables_variant ?? null,
                'specialization_id' => $pre->specialization_id ?? ($pre->specialization->id ?? null),
                'specialization_name' => $pre->specialization->name ?? null,
                'notes' => $dep->notes ?? null,
            ];
        });

        $combined = $itemsPayload->merge($depsPayload)->values();

        return response()->json([
            'success' => true,
            'data' => $combined,
        ], 200);
    }

    public function update(UpdateFicheNavetteRequest $request, $id)
    {
        $validated = $request->validated();

        $fiche = FicheNavette::findOrFail($id);

        // If status is being updated to 'arrived', set arrival_time if not already set
        if (array_key_exists('status', $validated)) {
            $newStatus = $validated['status'];
            if ($newStatus === 'arrived') {
                if (empty($fiche->arrival_time)) {
                    $fiche->arrival_time = now();
                }
            }
        }

        // Only update fillable fields - merge validated into the model
        foreach ($validated as $key => $value) {
            // protect id
            if ($key === 'id') {
                continue;
            }
            $fiche->{$key} = $value;
        }

        $fiche->save();

        return response()->json([
            'success' => true,
            'message' => 'Fiche Navette updated successfully',
            'data' => new FicheNavetteResource($fiche->load(['patient', 'creator', 'items.prestation'])),
        ], 200);
    }

    public function destroy($id)
    {
        $ficheNavette = FicheNavette::findOrFail($id);
        $ficheNavette->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fiche Navette deleted successfully',
        ]);
    }

    /**
     * Print fiche navette ticket and mark as arrived
     */
    public function printFicheNavetteTicket($id)
    {
        try {
            // Do not eager-load `items.appointment` - fiche_navette_items has no relation
            // that maps to an `appointments` foreign key. We'll query appointment_prestations
            // explicitly below when needed.
            $fiche = FicheNavette::with([
                'patient',
                'creator',
                'items.prestation',
            ])->findOrFail($id);

            // Get all items with their appointment dates if available
            $items = $fiche->items->map(function ($item) use ($fiche) {
                // Try to get appointment date from the appointment prestation
                $appointmentDate = null;

                if ($item->prestation_id) {
                    // Look for appointment prestation that matches
                    $appointmentPrestation = \App\Models\Appointment\AppointmentPrestation::with('appointment')
                        ->where('prestation_id', $item->prestation_id)
                        ->whereHas('appointment', function ($q) use ($fiche) {
                            // Use the fiche's patient id (we already loaded fiche)
                            $q->where('patient_id', $fiche->patient->id)
                                ->whereDate('appointment_date', '>=', Carbon::today());
                        })
                        ->first();

                    if ($appointmentPrestation && $appointmentPrestation->appointment) {
                        $appointmentDate = $appointmentPrestation->appointment->appointment_date;
                    }
                }

                $item->appointment_date = $appointmentDate;

                return $item;
            });

            // Generate ONE QR code for the entire fiche navette
            $qrData = 'FICHE-'.$fiche->id.'-'.
                     $fiche->patient->id.'-'.
                     Carbon::parse($fiche->fiche_date)->format('Ymd');

            // Build PNG QR using the Builder API (v6 uses new Builder())
            $builder = new Builder(
                writer: new PngWriter,
                data: $qrData,
                size: 250,
                margin: 10
            );

            $result = $builder->build();

            // Single QR code for the entire fiche - contains fiche ID, patient ID, and date
            $ficheQrCode = '<img src="'.$result->getDataUri().'" alt="Fiche QR Code" style="display: block; margin: 10px auto;" />';

            // Update fiche status to 'arrived' and set arrival_time
            $fiche->status = 'arrived';
            if (! $fiche->arrival_time) {
                $fiche->arrival_time = Carbon::now();
            }
            $fiche->save();

            // Get printed by user name
            $printedBy = Auth::user()->name ?? 'System';

            // Generate PDF
            $pdf = Pdf::loadView('pdf.fiche-navette-ticket', [
                'fiche' => $fiche,
                'items' => $items,
                'ficheQrCode' => $ficheQrCode,
                'printedBy' => $printedBy,
            ]);

            return $pdf->stream("fiche-navette-ticket-{$id}.pdf");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to print ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                    'price' => $prestation->price_with_vat_and_consumables_variant,
                    'duration' => $prestation->default_duration_minutes,
                    'service_id' => $prestation->service_id,
                    'service_name' => $prestation->service->name ?? '',
                    'need_an_appointment' => $prestation->need_an_appointment,
                    'specialization_id' => $prestation->specialization_id,
                    'specialization_name' => $prestation->specialization->name ?? '',
                    'required_prestations_info' => $prestation->required_prestations_info,
                    'type' => 'prestation',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $prestations,
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
                            'price' => $item->prestation->price_with_vat_and_consumables_variant,
                            'quantity' => 1,
                        ];
                    }),
                    'type' => 'package',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages,
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
        if (! is_array($prestationIds) || empty($prestationIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $dependencies = [];

        foreach ($prestationIds as $prestationId) {
            $prestation = Prestation::find($prestationId);

            if (! $prestation || empty($prestation->required_prestations_info)) {
                continue;
            }

            // required_prestations_info is stored as JSON in DB
            $dependencyIds = is_array($prestation->required_prestations_info)
                ? $prestation->required_prestations_info
                : json_decode($prestation->required_prestations_info, true);

            if (! is_array($dependencyIds) || empty($dependencyIds)) {
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
                        'price' => $dep->price_with_vat_and_consumables_variant,
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
            $searchTerm = '%'.$request->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('internal_code', 'like', $searchTerm)
                    ->orWhere('billing_code', 'like', $searchTerm);
            });
        }

        // Filter by services
        if ($request->has('services') && ! empty($request->services)) {
            $query->whereIn('service_id', $request->services);
        }

        // Filter by specializations
        if ($request->has('specializations') && ! empty($request->specializations)) {
            $query->whereIn('specialization_id', $request->specializations);
        }

        $prestations = $query->paginate($request->input('per_page', 10));

        $formattedPrestations = $prestations->getCollection()->map(function ($prestation) {
            return [
                'id' => $prestation->id,
                'name' => $prestation->name,
                'internal_code' => $prestation->internal_code,
                'description' => $prestation->description,
                'price' => $prestation->price_with_vat_and_consumables_variant,
                'service_id' => $prestation->service_id,
                'service_name' => $prestation->service->name ?? '',
                'need_an_appointment' => $prestation->need_an_appointment,

                'specialization_id' => $prestation->specialization_id,
                'required_prestations_info' => $prestation->required_prestations_info ?? [],
                'specialization_name' => $prestation->specialization->name ?? '',
                'default_duration_minutes' => $prestation->default_duration_minutes,
                'selected_doctor_id' => null,
                'doctor_name' => '',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedPrestations,
            'pagination' => [
                'current_page' => $prestations->currentPage(),
                'per_page' => $prestations->perPage(),
                'total' => $prestations->total(),
                'last_page' => $prestations->lastPage(),
            ],
        ]);
    }

    public function getAllSpecializations(): JsonResponse
    {
        $specializations = Specialization::where('is_active', true)
            ->get()
            ->map(function ($specialization) {
                return [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $specializations,
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

    /**
     * Toggle patient's faithful status
     * Returns full fiche with updated patient data in FicheNavetteResource format
     */
    public function togglePatientFaithful(Request $request, $ficheNavetteId)
    {
        try {
            // Load fiche with patient and all required relations
            $fiche = FicheNavette::with([
                'creator:id,name',
                'patient:id,Firstname,Lastname,balance,is_faithful',
                'items.prestation',
                'items.doctor',
                'items.dependencies.dependencyPrestation'
            ])->findOrFail($ficheNavetteId);

            $patient = $fiche->patient;

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found',
                ], 404);
            }

            // Toggle the is_faithful status
            $patient->is_faithful = !$patient->is_faithful;
            $patient->save();

            // Reload the fiche to get fresh data with updated patient
            $fiche = $fiche->fresh();
            $fiche->load([
                'creator:id,name',
                'patient:id,Firstname,Lastname,balance,is_faithful',
                'items.prestation',
                'items.doctor',
                'items.dependencies.dependencyPrestation'
            ]);

            return response()->json([
                'success' => true,
                'message' => $patient->is_faithful 
                    ? 'Patient marked as faithful' 
                    : 'Patient marked as unfaithful',
                'data' => new FicheNavetteResource($fiche),
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle patient faithful status', [
                'error' => $e->getMessage(),
                'fiche_id' => $ficheNavetteId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update patient status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

public function getPrestationsBySpecialization($specializationId): JsonResponse
{
    $prestations = Prestation::with(['service', 'specialization', 'modalityType'])
        ->where('specialization_id', $specializationId)
        ->where('is_active', true)
        ->get()
        ->map(function ($prestation) {
            // Check if it's a consultation based on service name OR prestation name
            $isConsultation = $this->isConsultationType($prestation);
            
            return [
                'id' => $prestation->id,
                'name' => $prestation->name,
                'internal_code' => $prestation->internal_code,
                'description' => $prestation->description,
                'price' => $prestation->price_with_vat_and_consumables_variant,
                'duration' => $prestation->default_duration_minutes,
                'service_id' => $prestation->service_id,
                'need_an_appointment' => $prestation->need_an_appointment,
                'service_name' => $prestation->service->name ?? '',
                'specialization_id' => $prestation->specialization_id,
                'specialization_name' => $prestation->specialization->name ?? '',
                'required_prestations_info' => $prestation->required_prestations_info,
                'patient_instructions' => $prestation->patient_instructions,
                'type' => $isConsultation ? 'consultation' : 'prestation',
                'is_consultation' => $isConsultation,
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $prestations,
        'meta' => [
            'total' => $prestations->count(),
            'consultations' => $prestations->where('is_consultation', true)->count(),
            'prestations' => $prestations->where('is_consultation', false)->count(),
        ]
    ]);
}

/**
 * Check if a prestation is a consultation type
 * Checks both service name and prestation name for consultation keywords
 */
private function isConsultationType($prestation): bool
{
    // Keywords to check for consultation type (case-insensitive)
    $consultationKeywords = [
        'consultation',
        'consult',
        'consul',
        'visite',
        'examen clinique',
        'avis médical',
        'rendez-vous médical'
    ];
    
    // Convert to lowercase for case-insensitive comparison
  
    $prestationName = strtolower($prestation->name ?? '');
    
    // Check if any keyword exists in service name, prestation name, or description
    foreach ($consultationKeywords as $keyword) {
        $keyword = strtolower($keyword);
        
        if (str_contains($prestationName, $keyword)) {
            return true;
        }
    }
    
    return false;
}




    public function getPackagesBySpecialization($specializationId): JsonResponse
    {
        $packages = PrestationPackage::with(['items.prestation'])
            ->whereHas('items.prestation', function ($query) use ($specializationId) {
                $query->where('specialization_id', $specializationId);
            })
            ->get()
            ->map(function ($package) {
                $prestations = $package->items->map(function ($item) {
                    return [
                        'id' => $item->prestation->id,
                        'name' => $item->prestation->name,
                        'price' => $item->prestation->price_with_vat_and_consumables_variant,
                        'quantity' => 1,
                    ];
                });
                
                // Calculate package price as sum of prestations with VAT and consumables
                $calculatedPrice = $prestations->sum('price');
                
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $calculatedPrice,
                    'specialization_id' => $package->items->first()->prestation->specialization_id ?? null,
                    'prestations' => $prestations,
                    'type' => 'package',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages,
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
            'data' => $doctors,
        ]);
    }

    public function getAllPrestations(Request $request)
    {
        try {
            $prestations = Prestation::with(['service', 'specialization'])
                ->get();
            return response()->json([
                'success' => true,
                'data' => PrestationResource::collection($prestations),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prestations',
                'error' => $e->getMessage(),
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
                    'price' => $prestation->price_with_vat_and_consumables_variant,
                    'service_id' => $prestation->service_id,
                    'service_name' => $prestation->service->name ?? '',
                    'specialization_id' => $prestation->specialization_id,
                    'specialization_name' => $prestation->specialization->name ?? '',
                    'default_duration_minutes' => $prestation->default_duration_minutes,
                    'package_name' => $packageName,
                    'selected_doctor_id' => null,
                    'doctor_name' => '',
                    'type' => 'prestation',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $prestations,
        ]);
    }

    private function generateReference(): string
    {
        $prefix = 'FN';
        $date = now()->format('Ymd');
        $sequence = FicheNavette::whereDate('created_at', today())->count() + 1;

        return $prefix.$date.sprintf('%04d', $sequence);
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
            'data' => \App\Http\Resources\Reception\FichePatientSummaryResource::collection($result),
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
                    'data' => $item,
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
                        'data' => $dep,
                    ]);
                }

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Dependency found but no updatable status column.',
                ], 400);
            }

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Prestation or dependency not found.',
            ], 404);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getPrestationsForTodayAndPendingByAuthenticatedUser(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();
        if (! $user) {
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
                    if (isset($s->specialization_id)) {
                        return (int) $s->specialization_id;
                    }
                    if (isset($s->specialization) && isset($s->specialization->id)) {
                        return (int) $s->specialization->id;
                    }
                    if (isset($s->id)) {
                        return (int) $s->id;
                    }

                    return null;
                })->filter()->unique()->values()->toArray();
            } catch (\Throwable $ex) {
                $specIds = [];
            }
        }

        if (empty($specIds)) {
            if (isset($user->specialization_id) && $user->specialization_id) {
                $specIds = [(int) $user->specialization_id];
            } elseif (isset($user->specialization_ids) && is_array($user->specialization_ids) && ! empty($user->specialization_ids)) {
                $specIds = array_map('intval', $user->specialization_ids);
            } elseif (isset($user->specializations) && is_array($user->specializations) && ! empty($user->specializations)) {
                $specIds = array_values(array_filter(array_map(function ($s) {
                    if (is_array($s) && isset($s['id'])) {
                        return (int) $s['id'];
                    }
                    if (is_object($s) && isset($s->id)) {
                        return (int) $s->id;
                    }

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
        $fiches = FicheNavette::with(['items.prestation.specialization', 'items.doctor', 'patient'])
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

        // Filter fiche items by user's specialization ids AND payment rules
        $filteredItems = $allItems->filter(function ($item) use ($specIds) {
            $prestation = $item->prestation ?? null;
            if (! $prestation) {
                return false;
            }

            // Check specialization match
            $sid = null;
            if (isset($prestation->specialization_id) && $prestation->specialization_id) {
                $sid = (int) $prestation->specialization_id;
            } elseif (isset($prestation->specialization) && isset($prestation->specialization->id)) {
                $sid = (int) $prestation->specialization->id;
            }
            if ($sid === null || ! in_array($sid, $specIds, true)) {
                return false;
            }

            // Apply payment filtering rules
            $paymentType = strtolower($prestation->default_payment_type ?? 'post-paiement');
            $paymentStatus = strtolower($item->payment_status ?? '');

            // For pre-paid (Pré-paiement): only show if payment_status is 'partial' or 'paid'
            if (strpos($paymentType, 'pr') === 0 || $paymentType === 'pre-paid' || $paymentType === 'prepaid') {
                $allowedStatuses = ['partial', 'paid', 'partially_paid', 'partial_paid'];

                return in_array($paymentStatus, $allowedStatuses);
            }

            // For post-paid (Post-paiement) or Versement: show all
            return true;
        });

        // Collect parent item ids to load dependencies
        $parentItemIds = $allItems->pluck('id')->unique()->values()->toArray();

        // Load dependencies for those parent items
        $dependencies = ItemDependency::with(['dependencyPrestation.specialization'])
            ->whereIn('parent_item_id', $parentItemIds)
            ->get();

        // Filter dependencies by specialization AND payment rules
        $filteredDeps = $dependencies->filter(function ($dep) use ($specIds) {
            $pre = $dep->dependencyPrestation ?? null;
            if (! $pre) {
                return false;
            }

            // Check specialization match
            $sid = $pre->specialization_id ?? ($pre->specialization->id ?? null);
            if ($sid === null || ! in_array((int) $sid, $specIds, true)) {
                return false;
            }

            // Apply payment filtering rules
            $paymentType = strtolower($pre->default_payment_type ?? 'post-paiement');
            $paymentStatus = strtolower($dep->payment_status ?? '');

            // For pre-paid (Pré-paiement): only show if payment_status is 'partial' or 'paid'
            if (strpos($paymentType, 'pr') === 0 || $paymentType === 'pre-paid' || $paymentType === 'prepaid') {
                $allowedStatuses = ['partial', 'paid', 'partially_paid', 'partial_paid'];

                return in_array($paymentStatus, $allowedStatuses);
            }

            // For post-paid (Post-paiement) or Versement: show all
            return true;
        });

        // Map fiche items payload
        $itemsPayload = $filteredItems->map(function ($item) {
            $prestation = $item->prestation;
            $fiche = $item->fiche ?? null;

            // Get doctor name by finding the Doctor model using doctor_id
            $doctorName = null;
            if ($item->doctor_id) {
                $doctor = \App\Models\Doctor::with('user')->find($item->doctor_id);
                if ($doctor && $doctor->user) {
                    $doctorName = $doctor->user->name ?? ($doctor->user->Firstname && $doctor->user->Lastname ? $doctor->user->Firstname.' '.$doctor->user->Lastname : null);
                }
            }

            return [
                'type' => 'item',
                'id' => $item->id,
                'fiche_navette_id' => $item->fiche_navette_id,
                'fiche_reference' => $fiche?->reference ?? null,
                'fiche_status' => $fiche?->status ?? null,
                'patient_name' => $fiche?->patient?->Firstname.' '.$fiche?->patient?->Lastname ?? null,
                'patient_phone' => $fiche?->patient?->phone ?? null,
                'fiche_date' => $fiche?->fiche_date ?? null,
                'status' => $item->status ?? null,
                'paid_amount' => $item->paid_amount ?? null,
                'remaining_amount' => $item->remaining_amount ?? null,
                'prestation_id' => $prestation->id ?? null,
                'prestation_name' => $prestation->name ?? null,
                'price' => $item->amount ?? $prestation->price_with_vat_and_consumables_variant ?? null,
                'specialization_id' => $prestation->specialization_id ?? ($prestation->specialization->id ?? null),
                'specialization_name' => $prestation->specialization->name ?? null,
                'payment_status' => $item->payment_status ?? null,
                'payment_type' => $prestation->default_payment_type ?? 'post-paid',
                'doctor_id' => $item->doctor_id ?? null,
                'doctor_name' => $doctorName,
                'notes' => $item->notes ?? null,
            ];
        });

        // Map dependency payloads and attach fiche info using parent_item_id -> itemMap
        $depsPayload = $filteredDeps->map(function ($dep) use ($itemMap) {
            $pre = $dep->dependencyPrestation;
            $parent = $itemMap->get($dep->parent_item_id);
            $fiche = $parent->fiche ?? null;

            // Get doctor name by finding the Doctor model using doctor_id from parent
            $doctorName = null;
            if ($parent && $parent->doctor_id) {
                $doctor = \App\Models\Doctor::with('user')->find($parent->doctor_id);
                if ($doctor && $doctor->user) {
                    $doctorName = $doctor->user->name ?? ($doctor->user->Firstname && $doctor->user->Lastname ? $doctor->user->Firstname.' '.$doctor->user->Lastname : null);
                }
            }

            return [
                'type' => 'dependency',
                'id' => 'dep_'.$dep->id,
                'dependency_id' => $dep->id,
                'parent_item_id' => $dep->parent_item_id,
                'fiche_navette_id' => $parent->fiche_navette_id ?? null,
                'fiche_reference' => $fiche?->reference ?? null,
                'fiche_status' => $fiche?->status ?? null,
                'patient_name' => $fiche?->patient?->Firstname.' '.$fiche?->patient?->Lastname ?? null,
                'patient_phone' => $fiche?->patient?->phone ?? null,
                'fiche_date' => $fiche?->fiche_date ?? null,
                'status' => $dep->status ?? 'pending',
                'paid_amount' => $dep->paid_amount ?? null,
                'remaining_amount' => $dep->remaining_amount ?? null,
                'prestation_id' => $pre->id ?? null,
                'prestation_name' => $pre->name ?? null,
                'price' => $dep->final_price ?? $dep->base_price ?? $pre->price_with_vat_and_consumables_variant ?? null,
                'specialization_id' => $pre->specialization_id ?? ($pre->specialization->id ?? null),
                'specialization_name' => $pre->specialization->name ?? null,
                'payment_status' => $dep->payment_status ?? null,
                'payment_type' => $pre->default_payment_type ?? 'post-paid',
                'doctor_id' => $parent->doctor_id ?? null,
                'doctor_name' => $doctorName,
                'notes' => $dep->notes ?? null,
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
