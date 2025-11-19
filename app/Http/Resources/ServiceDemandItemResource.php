<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceDemandItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine product source and extract product data
        $productSource = null;
        $productData = null;

        if ($this->product_id && $this->product) {
            $productSource = 'stock';
            $productData = [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'code_interne' => $this->product->code_interne,
                'designation' => $this->product->designation,
                'forme' => $this->product->forme,
                'unit' => $this->product->forme,
            ];
        } elseif ($this->pharmacy_product_id && $this->pharmacyProduct) {
            $productSource = 'pharmacy';
            $productData = [
                'id' => $this->pharmacyProduct->id,
                'name' => $this->pharmacyProduct->name,
                'sku' => $this->pharmacyProduct->sku,
                'unit_of_measure' => $this->pharmacyProduct->unit_of_measure,
                'brand_name' => $this->pharmacyProduct->brand_name,
            ];
        }

        return [
            'id' => $this->id,
            'service_demand_purchasing_id' => $this->service_demand_purchasing_id,
            'product_id' => $this->product_id,
            'pharmacy_product_id' => $this->pharmacy_product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'quantity_by_box' => (bool) $this->quantity_by_box,
            'notes' => $this->notes,
            'status' => $this->status,
            'product_source' => $productSource,
            'product' => $productData,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
