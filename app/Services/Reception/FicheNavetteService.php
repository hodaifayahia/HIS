<?php

namespace App\Services\Reception;

use App\Models\Appointment\AppointmentPrestation;
use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\Doctor;
use App\Models\DoctorEmergencyPlanning;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Specialization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FicheNavetteService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = ficheNavette::with([
            'creator:id,name',
            'patient:id,Firstname,Lastname,balance',
            'emergencyDoctor.user:id,name',
            'emergencyDoctor.specialization:id,name',
            'items.prestation:id,name,internal_code,public_price,specialization_id',
            'items.prestation.specialization:id,name',
            'items.dependencies:id,dependency_type,notes,payment_status,dependent_prestation_id,is_package',
            'items.dependencies.dependencyPrestation:id,name,internal_code,public_price',
            'items.nursingConsumptions.product',
            'items.nursingConsumptions.pharmacy',
        ])
            ->select([
                'id', 'patient_id', 'creator_id', 'status', 'fiche_date',
                'total_amount', 'created_at', 'updated_at', 'is_emergency', 'emergency_doctor_id',
            ]);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function create(array $data): ficheNavette
    {
        return DB::transaction(function () use ($data) {
            $isEmergency = $data['is_emergency'] ?? false;
            $emergencyDoctorId = null;

            // Auto-assign emergency doctor if this is an emergency case
            if ($isEmergency) {
                $emergencyDoctorId = $this->findAvailableEmergencyDoctor();
            }

            $ficheNavette = ficheNavette::create([
                'patient_id' => $data['patient_id'],
                'companion_id' => $data['companion_id'] ?? null,
                'is_emergency' => $isEmergency,
                'emergency_doctor_id' => $emergencyDoctorId,
                'creator_id' => Auth::id(),
                'status' => 'pending',
                'fiche_date' => now(),
                'total_amount' => 0,
            ]);

            $this->addAppointmentPrestations($ficheNavette, $data['patient_id']);

            return $ficheNavette->load(['patient', 'creator', 'emergencyDoctor.user']);
        });
    }

    public function find(int $id): ?ficheNavette
    {
        return ficheNavette::with([
            'creator',
            'patient',
            'emergencyDoctor.user',
            'emergencyDoctor.specialization',
            'items.prestation',
            'items.nursingConsumptions.product',
            'items.nursingConsumptions.pharmacy',
        ])->find($id);
    }

    public function delete(int $id): bool
    {
        $ficheNavette = ficheNavette::findOrFail($id);

        return $ficheNavette->delete();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return ficheNavette::where('id', $id)->update(['status' => $status]);
    }

    public function updatePrestationStatus(int $prestationId, string $status): array
    {
        return DB::transaction(function () use ($prestationId, $status) {
            // Handle dependency IDs with "dep_" prefix
            if (is_string($prestationId) && str_starts_with($prestationId, 'dep_')) {
                $prestationId = (int) substr($prestationId, 4);
            }

            // Try to update fiche navette item
            $item = ficheNavetteItem::with('prestation')->find($prestationId);
            if ($item) {
                $item->status = $status;
                $item->save();

                return ['success' => true, 'data' => $item, 'type' => 'item'];
            }

            // Try to update dependency
            $dep = ItemDependency::with('dependencyPrestation')->find($prestationId);
            if ($dep) {
                $updateColumn = $this->getStatusColumn($dep);
                if ($updateColumn) {
                    $dep->{$updateColumn} = $status;
                    $dep->save();

                    return ['success' => true, 'data' => $dep, 'type' => 'dependency'];
                }

                return ['success' => false, 'message' => 'No updatable status column found'];
            }

            return ['success' => false, 'message' => 'Item or dependency not found'];
        });
    }

    public function getPrestationsForFiche(int $ficheId, User $user): array
    {
        $specIds = $this->getUserSpecializationIds($user);

        if (empty($specIds)) {
            return [];
        }

        $fiche = ficheNavette::with(['items.prestation.specialization', 'patient'])->find($ficheId);

        if (! $fiche) {
            return [];
        }

        return $this->filterPrestationsBySpecialization($fiche, $specIds);
    }

    public function getTodayAndPendingPrestations(User $user): array
    {
        $specIds = $this->getUserSpecializationIds($user);

        if (empty($specIds)) {
            return [];
        }

        $today = Carbon::today()->toDateString();

        $fiches = ficheNavette::with(['items.prestation.specialization'])
            ->where(function ($q) use ($today) {
                $q->whereDate('fiche_date', $today)
                    ->orWhereRaw('LOWER(status) = ?', ['pending']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($fiches->isEmpty()) {
            return [];
        }

        return $this->processFichePrestations($fiches, $specIds);
    }

    public function searchPrestations(array $filters): LengthAwarePaginator
    {
        $query = Prestation::with(['service', 'specialization'])
            ->where('is_active', true);

        if (! empty($filters['search'])) {
            $searchTerm = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('internal_code', 'like', $searchTerm)
                    ->orWhere('billing_code', 'like', $searchTerm);
            });
        }

        if (! empty($filters['services'])) {
            $query->whereIn('service_id', $filters['services']);
        }

        if (! empty($filters['specializations'])) {
            $query->whereIn('specialization_id', $filters['specializations']);
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function getPrestationsByService(int $serviceId): array
    {
        return Prestation::with(['service', 'specialization', 'modalityType'])
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($prestation) => $this->formatPrestationData($prestation))
            ->toArray();
    }

    public function getPrestationsBySpecialization(int $specializationId): array
    {
        return Prestation::with(['service', 'specialization', 'modalityType'])
            ->where('specialization_id', $specializationId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($prestation) => $this->formatPrestationData($prestation))
            ->toArray();
    }

    public function getPackagesByService(int $serviceId): array
    {
        return PrestationPackage::with(['items.prestation'])
            ->whereHas('items.prestation', fn ($query) => $query->where('service_id', $serviceId))
            ->get()
            ->map(fn ($package) => $this->formatPackageData($package))
            ->toArray();
    }

    public function getPackagesBySpecialization(int $specializationId): array
    {
        return PrestationPackage::with(['items.prestation'])
            ->whereHas('items.prestation', fn ($query) => $query->where('specialization_id', $specializationId))
            ->get()
            ->map(fn ($package) => $this->formatPackageData($package))
            ->toArray();
    }

    public function getDoctorsBySpecialization(int $specializationId): array
    {
        return Doctor::with(['user', 'specialization'])
            ->where('specialization_id', $specializationId)
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->user->id,
                    'name' => $doctor->user->name,
                    'specialization_id' => $doctor->specialization_id,
                    'specialization_name' => $doctor->specialization->name ?? '',
                ];
            })
            ->toArray();
    }

    public function getAllSpecializations(): array
    {
        return Specialization::where('is_active', true)
            ->get()
            ->map(fn ($specialization) => [
                'id' => $specialization->id,
                'name' => $specialization->name,
            ])
            ->toArray();
    }

    public function getAllPrestations(): array
    {
        return Prestation::with(['service', 'specialization', 'modalityType'])
            ->where('is_active', true)
            ->get()
            ->map(fn ($prestation) => $this->formatPrestationData($prestation))
            ->toArray();
    }

    public function getPrestationDependencies(array $prestationIds): array
    {
        if (empty($prestationIds)) {
            return [];
        }

        $dependencies = [];

        foreach ($prestationIds as $prestationId) {
            $prestation = Prestation::find($prestationId);

            if (! $prestation || empty($prestation->required_prestations_info)) {
                continue;
            }

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
                        'price' => $dep->public_price,
                        'parent_id' => $prestationId,
                    ];
                })
                ->toArray();

            $dependencies = array_merge($dependencies, $deps);
        }

        return $dependencies;
    }

    // Private helper methods
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $searchTerm = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference', 'like', $searchTerm)
                    ->orWhereHas('patient', fn ($q) => $q->where('Firstname', 'like', $searchTerm)
                        ->orWhere('Lastname', 'like', $searchTerm));
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_emergency']) && $filters['is_emergency'] !== '') {
            $query->where('is_emergency', $filters['is_emergency']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }
    }

    private function addAppointmentPrestations(ficheNavette $ficheNavette, int $patientId): void
    {
        $prestations = AppointmentPrestation::with('appointment')
            ->where('patient_id', $patientId)
            ->whereDate('appointment_date', Carbon::now()->startOfDay())
            ->get();

        if ($prestations->isNotEmpty()) {
            $ficheNavette->items()->createMany(
                $prestations->map(function ($item) {
                    return [
                        'prestation_id' => $item->id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                })->toArray()
            );
        }
    }

    private function getUserSpecializationIds(User $user): array
    {
        $user = User::with(['activeSpecializations.specialization', 'specializations', 'specialization'])->find($user->id) ?? $user;

        $specIds = [];

        if (isset($user->activeSpecializations) && $user->activeSpecializations) {
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
        }

        if (empty($specIds)) {
            if (isset($user->specialization_id) && $user->specialization_id) {
                $specIds = [(int) $user->specialization_id];
            } elseif (isset($user->specialization_ids) && is_array($user->specialization_ids)) {
                $specIds = array_map('intval', $user->specialization_ids);
            } elseif (isset($user->specializations) && is_array($user->specializations)) {
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

        return $specIds;
    }

    private function getStatusColumn($dependency): ?string
    {
        $table = $dependency->getTable();

        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'status')) {
            return 'status';
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'payment_status')) {
            return 'payment_status';
        }

        return null;
    }

    private function formatPrestationData($prestation): array
    {
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
            'type' => 'prestation',
        ];
    }

    private function formatPackageData($package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'price' => $package->price,
            'prestations' => $package->items->map(function ($item) {
                return [
                    'id' => $item->prestation->id,
                    'name' => $item->prestation->name,
                    'price' => $item->prestation->public_price,
                    'quantity' => 1,
                ];
            }),
            'type' => 'package',
        ];
    }

    private function filterPrestationsBySpecialization(ficheNavette $fiche, array $specIds): array
    {
        $allItems = $fiche->items ?? collect();

        $filteredItems = $allItems->filter(function ($item) use ($specIds) {
            $prestation = $item->prestation ?? null;
            if (! $prestation) {
                return false;
            }

            $sid = $prestation->specialization_id ?? ($prestation->specialization->id ?? null);

            return $sid !== null && in_array((int) $sid, $specIds, true);
        })->values();

        return $this->buildPrestationPayload($filteredItems, $fiche);
    }

    private function processFichePrestations($fiches, array $specIds): array
    {
        $allItems = $fiches->flatMap(function ($fiche) {
            return ($fiche->items ?? collect())->map(function ($item) use ($fiche) {
                $item->fiche = $fiche;

                return $item;
            });
        });

        if ($allItems->isEmpty()) {
            return [];
        }

        $filteredItems = $allItems->filter(function ($item) use ($specIds) {
            $prestation = $item->prestation ?? null;
            if (! $prestation) {
                return false;
            }

            $sid = $prestation->specialization_id ?? ($prestation->specialization->id ?? null);

            return $sid !== null && in_array((int) $sid, $specIds, true);
        });

        return $this->buildCombinedPayload($filteredItems, $allItems);
    }

    private function buildPrestationPayload($items, $fiche): array
    {
        return $items->map(function ($item) use ($fiche) {
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
                'price' => $item->amount ?? $prestation->public_price ?? null,
                'specialization_id' => $prestation->specialization_id ?? null,
                'specialization_name' => $prestation->specialization->name ?? null,
                'notes' => $item->notes ?? null,
            ];
        })->toArray();
    }

    private function buildCombinedPayload($filteredItems, $allItems): array
    {
        $itemMap = $allItems->keyBy('id');
        $parentItemIds = $allItems->pluck('id')->unique()->values()->toArray();

        // Load dependencies
        $dependencies = ItemDependency::with(['dependencyPrestation.specialization'])
            ->whereIn('parent_item_id', $parentItemIds)
            ->get();

        // Map items payload
        $itemsPayload = $filteredItems->map(function ($item) {
            $prestation = $item->prestation;
            $fiche = $item->fiche ?? null;

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
                'payment_status' => $item->payment_status ?? null,
                'paid_amount' => $item->paid_amount ?? null,
                'remaining_amount' => $item->remaining_amount ?? null,
                'prestation_id' => $prestation->id ?? null,
                'prestation_name' => $prestation->name ?? null,
                'price' => $item->amount ?? $prestation->public_price ?? null,
                'specialization_id' => $prestation->specialization_id ?? null,
                'specialization_name' => $prestation->specialization->name ?? null,
                'notes' => $item->notes ?? null,
            ];
        });

        return $itemsPayload->values()->toArray();
    }

    private function generateReference(): string
    {
        $prefix = 'FN';
        $date = now()->format('Ymd');
        $sequence = ficheNavette::whereDate('created_at', today())->count() + 1;

        return $prefix.$date.sprintf('%04d', $sequence);
    }

    /**
     * Find an available emergency doctor based on current time and DoctorEmergencyPlanning
     *
     * @return int|null The doctor ID if found, null otherwise
     */
    private function findAvailableEmergencyDoctor(): ?int
    {
        $now = Carbon::now();
        $currentDate = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // Find doctors who are scheduled for emergency duty right now
        $planning = DoctorEmergencyPlanning::with('doctor')
            ->where('planning_date', $currentDate)
            ->where('is_active', true)
            ->where('shift_start_time', '<=', $currentTime)
            ->where('shift_end_time', '>=', $currentTime)
            ->orderBy('shift_start_time', 'asc')
            ->first();

        if ($planning && $planning->doctor) {
            return $planning->doctor_id;
        }

        // Fallback: Try to find any emergency doctor scheduled for today
        $todayPlanning = DoctorEmergencyPlanning::with('doctor')
            ->where('planning_date', $currentDate)
            ->where('is_active', true)
            ->orderBy('shift_start_time', 'asc')
            ->first();

        if ($todayPlanning && $todayPlanning->doctor) {
            return $todayPlanning->doctor_id;
        }

        // No emergency doctor found
        return null;
    }
}
