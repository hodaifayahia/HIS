<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'prestation_package_id' => $item->prestation_package_id,
                    'prestation_id' => $item->prestation_id,
                    'prestation' => $item->prestation ? [
                        'id' => $item->prestation->id,
                        'name' => $item->prestation->name,
                        'internal_code' => $item->prestation->internal_code,
                        // Raw public price (without VAT) - fallback to 0.0
                        'public_price' => $item->prestation->public_price !== null ? (float) $item->prestation->public_price : 0.0,
                        // Price including VAT and consumables (TTC) - use accessor which returns rounded float
                        'price_with_vat' => method_exists($item->prestation, 'getPriceWithVatAttribute')
                            ? (float) $item->prestation->getPriceWithVatAttribute()
                            : 0.0,
                        'description' => $item->prestation->description,
                        // Add other prestation fields as needed
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
