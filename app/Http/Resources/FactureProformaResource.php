<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FactureProformaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'factureProformaCode' => $this->factureProformaCode,
            'date' => $this->date instanceof \Carbon\Carbon ? $this->date->format('Y-m-d') : $this->date,
            'fournisseur_id' => $this->fournisseur_id,
            'service_demand_purchasing_id' => $this->service_demand_purchasing_id,
            'created_by' => $this->created_by,
            'status' => $this->status ?? 'draft',
            'notes' => $this->notes,
            'pdf_content' => $this->pdf_content,
            'pdf_generated_at' => $this->pdf_generated_at instanceof \Carbon\Carbon ? $this->pdf_generated_at->format('Y-m-d H:i:s') : $this->pdf_generated_at,
            'is_confirmed' => $this->is_confirmed ?? false,
            'confirmed_at' => $this->confirmed_at instanceof \Carbon\Carbon ? $this->confirmed_at->format('Y-m-d H:i:s') : $this->confirmed_at,
            'confirmed_by' => $this->confirmed_by,
            'attachments' => $this->attachments ?? [],
            'created_at' => $this->created_at instanceof \Carbon\Carbon ? $this->created_at->format('Y-m-d H:i:s') : $this->created_at,
            'updated_at' => $this->updated_at instanceof \Carbon\Carbon ? $this->updated_at->format('Y-m-d H:i:s') : $this->updated_at,

            // Relationships
            'fournisseur' => $this->whenLoaded('fournisseur', function () {
                return [
                    'id' => $this->fournisseur->id,
                    'company_name' => $this->fournisseur->company_name,
                    'contact_person' => $this->fournisseur->contact_person,
                    'email' => $this->fournisseur->email,
                    'phone' => $this->fournisseur->phone,
                    'address' => $this->fournisseur->address,
                ];
            }),

            'service_demand' => $this->whenLoaded('serviceDemand', function () {
                return [
                    'id' => $this->serviceDemand->id,
                    'request_code' => $this->serviceDemand->request_code,
                    'status' => $this->serviceDemand->status,
                    'created_at' => $this->serviceDemand->created_at instanceof \Carbon\Carbon ? $this->serviceDemand->created_at->format('Y-m-d H:i:s') : $this->serviceDemand->created_at,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_id' => $product->product_id,
                        'quantity' => $product->quantity,
                        'quantity_sended' => $product->quantity_sended ?? 0,
                        'price' => $product->price,
                        'unit' => $product->unit,
                        'confirmation_status' => $product->confirmation_status ?? 'pending',
                        'confirmed_at' => $product->confirmed_at instanceof \Carbon\Carbon ? $product->confirmed_at->format('Y-m-d H:i:s') : $product->confirmed_at,
                        'status' => $product->status,
                        'created_at' => $product->created_at instanceof \Carbon\Carbon ? $product->created_at->format('Y-m-d H:i:s') : $product->created_at,
                        'updated_at' => $product->updated_at instanceof \Carbon\Carbon ? $product->updated_at->format('Y-m-d H:i:s') : $product->updated_at,

                        // Product relationship
                        'product' => $product->product ? [
                            'id' => $product->product->id,
                            'name' => $product->product->name,
                            'code_interne' => $product->product->code_interne,
                            'category' => $product->product->category,
                            'forme' => $product->product->forme,
                            'unit' => $product->product->unit,
                            'is_clinical' => $product->product->is_clinical ?? false,
                            'description' => $product->product->description,
                        ] : null,
                    ];
                });
            }),

            // Computed fields
            'total_amount' => $this->when($this->relationLoaded('products'), function () {
                return $this->products->sum(function ($product) {
                    return ($product->quantity ?? 0) * ($product->price ?? 0);
                });
            }),

            'total_products' => $this->when($this->relationLoaded('products'), function () {
                return $this->products->count();
            }),

            'completion_percentage' => $this->when($this->relationLoaded('products'), function () {
                if ($this->products->count() === 0) {
                    return 0;
                }

                $totalProducts = $this->products->count();
                $completedProducts = $this->products->filter(function ($product) {
                    return ($product->quantity_sended ?? 0) > 0;
                })->count();

                return round(($completedProducts / $totalProducts) * 100, 2);
            }),

            'can_confirm' => $this->when($this->relationLoaded('products'), function () {
                return $this->products->every(function ($product) {
                    return ($product->quantity_sended ?? 0) > 0 &&
                           ($product->quantity_sended ?? 0) <= ($product->quantity ?? 0);
                });
            }),
        ];
    }
}
