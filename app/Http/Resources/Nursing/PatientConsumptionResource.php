<?php

namespace App\Http\Resources\Nursing;

use Illuminate\Http\Resources\Json\JsonResource;

class PatientConsumptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fiche_id' => $this->fiche_id,
            'fiche_navette_item_id' => $this->fiche_navette_item_id,
            'product' => [
                'id' => $this->product?->id ?? $this->product_id,
                'name' => $this->product?->name,
                'code_interne' => $this->product->code_interne ?? null,
                'designation' => $this->product->designation ?? null,
                'is_clinical' => $this->product->is_clinical ?? null,
            ],
            'pharmacy_product' => $this->pharmacy ? [
                'id' => $this->pharmacy->id,
                'name' => $this->pharmacy->name,
                'selling_price' => $this->pharmacy->selling_price,
                'unit_cost' => $this->pharmacy->unit_cost,
                'barcode' => $this->pharmacy->barcode,
            ] : null,
            'quantity' => $this->quantity,
            'consumed_by' => $this->consumed_by,
            'unit_price' => $this->getUnitPrice(),
            'total_price' => $this->getTotalPrice(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'fiche_navette_item' => $this->whenLoaded('ficheNavetteItem', function () {
                $uploaded = $this->ficheNavetteItem->uploaded_file ? json_decode($this->ficheNavetteItem->uploaded_file, true) : null;

                return [
                    'id' => $this->ficheNavetteItem->id,
                    'status' => $this->ficheNavetteItem->status,
                    'base_price' => $this->ficheNavetteItem->base_price,
                    'final_price' => $this->ficheNavetteItem->final_price,
                    'patient_share' => $this->ficheNavetteItem->patient_share,
                    'remaining_amount' => $this->ficheNavetteItem->remaining_amount,
                    'is_nursing_consumption' => $this->ficheNavetteItem->is_nursing_consumption,
                    'uploaded_details' => $uploaded,
                ];
            }),
        ];
    }

    private function getUnitPrice(): ?float
    {
        $price = $this->pharmacy->selling_price ?? null;

        if ($price === null && isset($this->product->public_price)) {
            $price = $this->product->public_price;
        }

        if ($price === null && isset($this->product->price)) {
            $price = $this->product->price;
        }

        return $price !== null ? (float) $price : null;
    }

    private function getTotalPrice(): ?float
    {
        $unitPrice = $this->getUnitPrice();

        if ($unitPrice === null) {
            return null;
        }

        return round($unitPrice * max(1, (int) $this->quantity), 2);
    }
}
