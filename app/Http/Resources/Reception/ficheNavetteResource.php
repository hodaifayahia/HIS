<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class ficheNavetteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
  public function toArray($request)
{
    // determine items collection if loaded
    $itemsCollection = $this->whenLoaded('items', fn() => $this->items) ?? collect();

    // compute paid amount from item payment_status (use final_price or patient_share)
    $paidAmount = 0.0;
    if ($itemsCollection instanceof \Illuminate\Support\Collection) {
        $paidAmount = $itemsCollection->reduce(function ($carry, $item) {
            $price = (float) ($item->final_price ?? $item->patient_share ?? 0);
            if (isset($item->payment_status) && strtolower($item->payment_status) === 'paid') {
                return $carry + $price;
            }
            return $carry;
        }, 0.0);
    } else {
        // if it's array-like
        foreach ($itemsCollection ?? [] as $item) {
            $price = (float) ($item->final_price ?? $item->patient_share ?? ($item->prestation->public_price ?? 0));
            if (!empty($item->payment_status) && strtolower($item->payment_status) === 'paid') {
                $paidAmount += $price;
            }
        }
    }

    $total = (float) ($this->total_amount ?? 0);
    $remaining = max(0, $total - $paidAmount);

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
        'patient_name' => $this->whenLoaded('patient', fn() => $this->patient?->Firstname . ' ' . $this->patient?->Lastname),
        'patient_balance' => $this->whenLoaded('patient', fn() => $this->patient?->balance),
        'creator_id' => $this->creator_id,
        'creator_name' => $this->whenLoaded('creator', fn() => $this->creator?->name),
        'status' => $this->status,
        'fiche_date' => $this->fiche_date,
        'total_amount' => $this->total_amount,
        'paid_amount' => $paidAmount,
        'remaining_amount' => $remaining,
        'payment_status' => $fichePaymentStatus,
        'notes' => $this->notes,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,

        // Only include items if relation is loaded
        'items' => $this->whenLoaded('items', function () {
            return $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'prestation_id' => $item->prestation_id,
                    'prestation' => $this->whenLoadedRelation($item, 'prestation', function () use ($item) {
                        return $item->prestation ? [
                            'id' => $item->prestation->id,
                            'name' => $item->prestation->name,
                            'internal_code' => $item->prestation->internal_code,
                            'Urgent_Prestation' => $item->Urgent_Prestation,
                            'price' => $item->prestation->public_price,
                            'specialization_name' => $item->prestation->specialization?->name,
                        ] : null;
                    }),
                    'package_id' => $item->package_id,
                    'doctor_id' => $item->doctor_id,
                    'custom_name' => $item->custom_name,
                    'payment_status' => $item->payment_status,
                    'status' => $item->status,
                    'base_price' => $item->base_price,
                    'final_price' => $item->final_price,
                    'patient_share' => $item->patient_share,
                    'dependencies' => $this->whenLoadedRelation($item, 'dependencies', function () use ($item) {
                        return $item->dependencies->map(function ($dep) {
                            return [
                                'id' => $dep->id,
                                'dependency_type' => $dep->dependency_type,
                                'notes' => $dep->notes,
                                'payment_status' => $dep->payment_status,
                                'dependency_prestation' => $dep->dependencyPrestation ? [
                                    'id' => $dep->dependencyPrestation->id,
                                    'name' => $dep->dependencyPrestation->name,
                                    'internal_code' => $dep->dependencyPrestation->internal_code,
                                    'price' => $dep->dependencyPrestation->public_price,
                                    'is_package' => $dep->is_package,
                                ] : null,
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
