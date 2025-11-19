<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class FicheNavetteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // determine items collection if loaded
        $itemsCollection = $this->whenLoaded('items', fn () => $this->items) ?? collect();
        
        // Normalize items to collection for consistent handling
        $items = $itemsCollection instanceof \Illuminate\Support\Collection 
            ? $itemsCollection 
            : collect($itemsCollection ?? []);

        // Prefer summing item-level `final_price` / `paid_amount` / `remaining_amount`
        // when the `items` relation is loaded. Fallback to fiche-level stored
        // totals (`total_amount`, `paid_amount`, `remaining_amount`) when items
        // are not loaded or when the items collection is empty.
        if ($items->isNotEmpty()) {
            // Compute total by using the same final_price logic used in the
            // returned items: prefer the prestation's computed price when
            // relation is loaded, otherwise fall back to the stored final_price.
            $total = 0.0;
            $paidAmount = 0.0;

            foreach ($items as $item) {
                $price = 0.0;
                // Array-shaped item (e.g., from raw queries)
                if (is_array($item)) {
                    // For convention items, use stored final_price (patient_price)
                    if (!empty($item['convention_id'])) {
                        $price = (float) ($item['final_price'] ?? 0);
                    } elseif (!empty($item['prestation']) && is_array($item['prestation'])) {
                        $pd = $item['prestation']['price_with_vat_and_consumables_variant'] ?? null;
                        if (is_array($pd)) {
                            // prefer consumables-aware TTC when available
                            $price = (float) ($pd['ttc_with_consumables_vat'] ?? $pd['ttc'] ?? $item['final_price'] ?? 0);
                        } elseif (is_numeric($pd)) {
                            // model may return scalar combined TTC
                            $price = (float) $pd;
                        } else {
                            $price = (float) ($item['prestation']['price'] ?? $item['prestation']['public_price'] ?? $item['final_price'] ?? 0);
                        }
                    } else {
                        $price = (float) ($item['final_price'] ?? 0);
                    }

                    $paid = (float) ($item['paid_amount'] ?? 0);
                } else {
                    // Eloquent model item
                    // For convention items, use stored final_price (patient_price)
                    if ($item->convention_id ?? false) {
                        $price = (float) ($item->final_price ?? 0);
                    } elseif (method_exists($item, 'relationLoaded') && $item->relationLoaded('prestation') && $item->prestation) {
                        $pd = $item->prestation->price_with_vat_and_consumables_variant ?? null;
                        if (is_array($pd)) {
                            $price = (float) ($pd['ttc_with_consumables_vat'] ?? $pd['ttc'] ?? $item->final_price ?? 0);
                        } elseif (is_numeric($pd)) {
                            $price = (float) $pd;
                        } else {
                            $price = (float) ($item->prestation->price ?? $item->final_price ?? 0);
                        }
                    } else {
                        $price = (float) ($item->final_price ?? 0);
                    }

                    $paid = (float) ($item->paid_amount ?? 0);
                }

                $total += $price;
                $paidAmount += $paid;
            }

            // Ensure remaining is consistent with computed total and paid amount
            $remaining = max(0, $total - $paidAmount);
        } else {
            // Fallback to fiche-level fields (these may be precomputed/stored)
            $total = (float) ($this->total_amount ?? 0);
            $paidAmount = (float) ($this->paid_amount ?? 0);
            $remaining = (float) ($this->remaining_amount ?? 0);
        }

        // determine fiche-level payment status
        if ($total <= 0) {
            $fichePaymentStatus = $paidAmount > 0 ? 'paid' : 'none';
        } elseif ($paidAmount <= 0) {
            $fichePaymentStatus = 'pending';
        } elseif ($paidAmount >= $total) {
            $fichePaymentStatus = 'paid';
        } else {
            $fichePaymentStatus = 'partial';
        }

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->whenLoaded('patient', fn () => $this->patient?->Firstname.' '.$this->patient?->Lastname),
            'patient_balance' => $this->whenLoaded('patient', fn () => $this->patient?->balance),
            'patient' => $this->whenLoaded('patient', function () {
                return $this->patient ? [
                    'id' => $this->patient->id,
                    'Firstname' => $this->patient->Firstname,
                    'Lastname' => $this->patient->Lastname,
                    'balance' => $this->patient->balance,
                    'is_faithful' => $this->patient->is_faithful ?? true,
                ] : null;
            }),
            'creator_id' => $this->creator_id,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'status' => $this->status,
            'fiche_date' => $this->fiche_date,
            'is_emergency' => (bool) $this->is_emergency,
            'emergency_doctor_id' => $this->emergency_doctor_id ?? null,
            'emergency_doctor' => $this->whenLoaded('emergencyDoctor', function () {
                return $this->emergencyDoctor ? [
                    'id' => $this->emergencyDoctor->id,
                    'name' => $this->emergencyDoctor->user?->name ?? 'N/A',
                    'specialization' => $this->emergencyDoctor->specialization?->name ?? 'N/A',
                ] : null;
            }),
            'total_amount' => $total,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remaining,
            'payment_status' => $fichePaymentStatus,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // // Only include items if relation is loaded
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'prestation_id' => $item->prestation_id,
                        'prestation' => $this->whenLoadedRelation($item, 'prestation', function () use ($item) {
                            if ($item->prestation) {
                                // Get the full price with VAT and consumables VAT
                                $priceData = $item->prestation->price_with_vat_and_consumables_variant;
                                if (is_array($priceData)) {
                                    $finalPrice = (float) ($priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0.0);
                                } elseif (is_numeric($priceData)) {
                                    $finalPrice = (float) $priceData;
                                } else {
                                    $finalPrice = 0.0;
                                }

                                return [
                                    'id' => $item->prestation->id,
                                    'name' => $item->prestation->name,
                                    'internal_code' => $item->prestation->internal_code,
                                    'Urgent_Prestation' => $item->Urgent_Prestation,
                                    // Use the computed attribute exposed via $appends
                                    'min_versement_amount' => $item->prestation->min_versement_amount ?? null,
                                    'price' => $finalPrice,
                                    'public_price' => $finalPrice,
                                    'price_with_vat_and_consumables_variant' => $priceData,
                                    'specialization_name' => $item->prestation->specialization?->name,
                                ];
                            }
                            return null;
                        }),
                        'package_id' => $item->package_id,
                        'doctor_id' => $item->doctor_id,
                        'custom_name' => $item->custom_name,
                        'payment_status' => $item->payment_status,
                        'status' => $item->status,
                        'base_price' => $item->base_price,
                        // For convention items, use stored final_price (patient_price)
                        // For non-convention items, compute from prestation accessor
                        'final_price' => $item->convention_id ? (float) $item->final_price : (
                            $item->relationLoaded('prestation') && $item->prestation ? (
                                (float) (is_array($item->prestation->price_with_vat_and_consumables_variant)
                                    ? ($item->prestation->price_with_vat_and_consumables_variant['ttc_with_consumables_vat'] ?? $item->prestation->price_with_vat_and_consumables_variant['ttc'] ?? $item->final_price ?? 0.0)
                                    : (is_numeric($item->prestation->price_with_vat_and_consumables_variant) ? $item->prestation->price_with_vat_and_consumables_variant : $item->final_price ?? 0.0)
                                )
                            ) : $item->final_price
                        ),
                        'patient_share' => $item->patient_share,
                        'dependencies' => $this->whenLoadedRelation($item, 'dependencies', function () use ($item) {
                            return $item->dependencies->map(function ($dep) {
                                if ($dep->dependencyPrestation) {
                                    // Get the full price with VAT and consumables VAT
                                    $priceData = $dep->dependencyPrestation->price_with_vat_and_consumables_variant;
                                    if (is_array($priceData)) {
                                        $finalPrice = (float) ($priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0.0);
                                    } elseif (is_numeric($priceData)) {
                                        $finalPrice = (float) $priceData;
                                    } else {
                                        $finalPrice = 0.0;
                                    }

                                    return [
                                        'id' => $dep->id,
                                        'dependency_type' => $dep->dependency_type,
                                        'notes' => $dep->notes,
                                        'payment_status' => $dep->payment_status,
                                        'dependency_prestation' => [
                                            'id' => $dep->dependencyPrestation->id,
                                            'name' => $dep->dependencyPrestation->name,
                                            'internal_code' => $dep->dependencyPrestation->internal_code,
                                            'price' => $finalPrice,
                                            'public_price' => $finalPrice,
                                            'price_with_vat_and_consumables_variant' => $priceData,
                                            // Use the computed attribute exposed via $appends
                                            'min_versement_amount' => $dep->dependencyPrestation->min_versement_amount ?? null,
                                            'is_package' => $dep->is_package,
                                        ],
                                    ];
                                }
                                return [
                                    'id' => $dep->id,
                                    'dependency_type' => $dep->dependency_type,
                                    'notes' => $dep->notes,
                                    'payment_status' => $dep->payment_status,
                                    'dependency_prestation' => null,
                                ];
                            });
                        }),
                    ];
                });
            }),
        ];
    }

    // Helper to check if a relation is loaded on a model
    private function whenLoadedRelation($model, $relation, $callback)
    {
        return $model->relationLoaded($relation) ? $callback() : [];
    }
}
