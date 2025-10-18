<?php

namespace App\Services\Nursing;

use App\Models\Nursing\PatientConsumption;
use App\Models\Reception\ficheNavetteItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PatientConsumptionService
{
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return PatientConsumption::with(['product', 'ficheNavette', 'pharmacy', 'ficheNavetteItem'])
            ->when($filters['fiche_id'] ?? null, fn ($q, $v) => $q->where('fiche_id', $v))
            ->when($filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->paginate($perPage);
    }

    public function create(array $data): PatientConsumption
    {
        return DB::transaction(function () use ($data) {
            $consumption = PatientConsumption::create($data);

            if (! empty($data['fiche_id'])) {
                $item = $this->syncConsumptionWithNursingItem($consumption);
                $consumption->setRelation('ficheNavetteItem', $item);
            }

            return $consumption->fresh(['product', 'ficheNavette', 'pharmacy', 'ficheNavetteItem']);
        });
    }

    public function createMany(array $consumptions): array
    {
        return DB::transaction(function () use ($consumptions) {
            $createdRecords = [];
            $now = now();
            $itemsCache = [];

            foreach ($consumptions as $consumption) {
                $consumption['created_at'] = $now;
                $consumption['updated_at'] = $now;
                $consumption['consumed_by'] = auth()->id();

                // Create PatientConsumption
                $createdConsumption = PatientConsumption::create($consumption);
                $createdRecords[] = $createdConsumption->id;

                if (! empty($consumption['fiche_id'])) {
                    $ficheId = $consumption['fiche_id'];
                    $item = $itemsCache[$ficheId] ?? null;
                    if (! $item) {
                        $item = $this->getOrCreateNursingItem($ficheId, $createdConsumption->consumed_by);
                        $itemsCache[$ficheId] = $item;
                    }
                    $item = $this->syncConsumptionWithNursingItem($createdConsumption, $item);
                    $itemsCache[$ficheId] = $item;
                    $createdConsumption->setRelation('ficheNavetteItem', $item);
                }
            }

            return PatientConsumption::with(['product', 'ficheNavette', 'pharmacy', 'ficheNavetteItem'])
                ->whereIn('id', $createdRecords)
                ->get()
                ->all();
        });
    }

    public function update(PatientConsumption $patientConsumption, array $data): PatientConsumption
    {
        return DB::transaction(function () use ($patientConsumption, $data) {
            $oldFicheId = $patientConsumption->fiche_id;
            $oldItemId = $patientConsumption->fiche_navette_item_id;
            $patientConsumption->update($data);

            if ($oldFicheId !== $patientConsumption->fiche_id) {
                if ($oldItemId) {
                    $this->handleNursingItemAfterRemoval($oldItemId);
                }

                if ($patientConsumption->fiche_id) {
                    $item = $this->syncConsumptionWithNursingItem($patientConsumption);
                    $patientConsumption->setRelation('ficheNavetteItem', $item);
                } else {
                    $patientConsumption->update(['fiche_navette_item_id' => null]);
                }
            } elseif ($patientConsumption->fiche_navette_item_id) {
                $this->recalculateAggregatedItemTotalsById($patientConsumption->fiche_navette_item_id);
            }

            return $patientConsumption->fresh(['product', 'ficheNavette', 'pharmacy', 'ficheNavetteItem']);
        });
    }

    public function delete(PatientConsumption $patientConsumption): bool
    {
        return DB::transaction(function () use ($patientConsumption) {
            $itemId = $patientConsumption->fiche_navette_item_id;

            $deleted = $patientConsumption->delete();

            if ($deleted && $itemId) {
                $this->handleNursingItemAfterRemoval($itemId);
            }

            return $deleted;
        });
    }

    private function syncConsumptionWithNursingItem(PatientConsumption $consumption, ?ficheNavetteItem $existingItem = null): ficheNavetteItem
    {
        if (! $consumption->fiche_id) {
            throw new \InvalidArgumentException('Cannot sync nursing item without fiche_id');
        }

        $consumption->loadMissing('ficheNavette');

        $item = $existingItem ?: $this->getOrCreateNursingItem(
            $consumption->fiche_id,
            $consumption->consumed_by ?? auth()->id(),
            $consumption->ficheNavette?->patient_id
        );

        if ($consumption->fiche_navette_item_id !== $item->id) {
            $consumption->update(['fiche_navette_item_id' => $item->id]);
        }

        $this->recalculateAggregatedItemTotals($item);

        return $item->fresh();
    }

    private function getOrCreateNursingItem(int $ficheId, ?int $clinicianId = null, ?int $patientId = null): ficheNavetteItem
    {
        $attributes = [
            'fiche_navette_id' => $ficheId,
            'is_nursing_consumption' => true,
        ];

        $defaults = [
            'prestation_id' => null,
            'status' => 'pending',
            'base_price' => 0,
            'final_price' => 0,
            'patient_share' => 0,
            'organisme_share' => 0,
            'primary_clinician_id' => $clinicianId,
            'assistant_clinician_id' => null,
            'technician_id' => null,
            'modality_id' => null,
            'convention_id' => null,
            'patient_id' => $patientId,
            'uploaded_file' => null,
            'family_authorization' => null,
            'prise_en_charge_date' => null,
            'package_id' => null,
            'remise_id' => null,
            'insured_id' => null,
            'remaining_amount' => 0,
            'paid_amount' => 0,
            'payment_status' => 'pending',
            'payment_method' => null,
        ];

        $item = ficheNavetteItem::firstOrCreate($attributes, $defaults);

        $needsUpdate = false;

        if ($clinicianId && ! $item->primary_clinician_id) {
            $item->primary_clinician_id = $clinicianId;
            $needsUpdate = true;
        }

        if ($patientId && ! $item->patient_id) {
            $item->patient_id = $patientId;
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $item->save();
        }

        return $item;
    }

    private function recalculateAggregatedItemTotals(ficheNavetteItem $item): void
    {
        $item->loadMissing(['nursingConsumptions.product']);

        $baseTotal = 0;
        $finalTotal = 0;
        $uploadedDetails = [];

        foreach ($item->nursingConsumptions as $consumption) {
            $base = $this->calculateBasePrice($consumption);
            $final = $this->calculateFinalPrice($consumption);

            $baseTotal += $base;
            $finalTotal += $final;

            $uploadedDetails[] = [
                'consumption_id' => $consumption->id,
                'product_id' => $consumption->product_id,
                'quantity' => $consumption->quantity,
            ];
        }

        $paidAmount = $item->paid_amount ?? 0;
        $remainingAmount = max($finalTotal - $paidAmount, 0);

        $item->fill([
            'base_price' => $baseTotal,
            'final_price' => $finalTotal,
            'patient_share' => $finalTotal,
            'organisme_share' => 0,
            'remaining_amount' => $remainingAmount,
            'uploaded_file' => $uploadedDetails ? json_encode($uploadedDetails) : null,
        ]);

        $item->save();
    }

    private function recalculateAggregatedItemTotalsById(?int $itemId): void
    {
        if (! $itemId) {
            return;
        }

        $item = ficheNavetteItem::find($itemId);

        if ($item) {
            $this->recalculateAggregatedItemTotals($item);
        }
    }

    private function handleNursingItemAfterRemoval(int $itemId): void
    {
        $item = ficheNavetteItem::find($itemId);

        if (! $item) {
            return;
        }

        if (! $item->nursingConsumptions()->exists()) {
            $item->delete();

            return;
        }

        $this->recalculateAggregatedItemTotals($item);
    }

    /**
     * Calculate base price from consumption
     */
    private function calculateBasePrice(PatientConsumption $consumption): float
    {
        if (isset($consumption->base_price) && $consumption->base_price > 0) {
            return $consumption->base_price * $consumption->quantity;
        }

        if ($consumption->product && $consumption->product->price) {
            return $consumption->product->price * $consumption->quantity;
        }

        return 0;
    }

    /**
     * Calculate final price from consumption
     */
    private function calculateFinalPrice(PatientConsumption $consumption): float
    {
        if (isset($consumption->final_price) && $consumption->final_price > 0) {
            return $consumption->final_price * $consumption->quantity;
        }

        return $this->calculateBasePrice($consumption);
    }

    /**
     * Get product name for custom_name field
     */
    private function getProductName(PatientConsumption $consumption): ?string
    {
        return $consumption->product?->name ?? "Product ID: {$consumption->product_id}";
    }
}
