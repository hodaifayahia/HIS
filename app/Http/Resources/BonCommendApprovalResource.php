<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonCommendApprovalResource extends JsonResource
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
            'bon_commend_id' => $this->bon_commend_id,
            'bon_commend' => [
                'id' => $this->bonCommend->id,
                'code' => $this->bonCommend->bonCommendCode,
                'total_amount' => (float) $this->bonCommend->total_amount,
                'formatted_total_amount' => number_format($this->bonCommend->total_amount, 2).' DZD',
                'status' => $this->bonCommend->status,
                'supplier' => $this->bonCommend->fournisseur ? [
                    'id' => $this->bonCommend->fournisseur->id,
                    'name' => $this->bonCommend->fournisseur->company_name,
                ] : null,
                'items' => $this->bonCommend->items ? $this->bonCommend->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? $item->product->product_name ?? 'Unknown Product',
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name ?? $item->product->product_name,
                        ] : null,
                        'quantity' => $item->quantity,
                        'quantity_desired' => $item->quantity_desired,
                        'price' => (float) ($item->price ?? 0),
                        'original_quantity_desired' => $item->original_quantity_desired,
                        'modified_by_approver' => $item->modified_by_approver ?? false,
                    ];
                }) : [],
                'created_at' => $this->bonCommend->created_at?->toISOString(),
            ],
            'approval_person_id' => $this->approval_person_id,
            'approval_person' => [
                'id' => $this->approvalPerson->id,
                'name' => $this->approvalPerson->user->name,
                'title' => $this->approvalPerson->title,
                'max_amount' => (float) $this->approvalPerson->max_amount,
            ],
            'requested_by' => $this->requested_by,
            'requester' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
            ],
            'amount' => (float) $this->amount,
            'formatted_amount' => number_format($this->amount, 2).' DZD',
            'status' => $this->status,
            'status_label' => $this->status_label,
            'notes' => $this->notes,
            'approval_notes' => $this->approval_notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
