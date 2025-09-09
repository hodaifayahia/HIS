<?php

namespace App\Http\Resources\manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionBankRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tx = $this->transaction;
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'status' => $this->status,
            'is_approved' => (bool) ($this->is_approved ?? false),
            'requested_at' => $this->requested_at ? $this->requested_at->toDateTimeString() : null,
            'approved_at' => $this->approved_at ? $this->approved_at->toDateTimeString() : null,

            // requester / approver minimal summaries
            'requester' => $this->when($this->relationLoaded('requester') || $this->requester, function () {
                return [
                    'id' => $this->requester->id ?? null,
                    'name' => $this->requester->name ?? null,
                    'email' => $this->requester->email ?? null,
                ];
            }, null),

            'approver' => $this->when($this->relationLoaded('approver') || $this->approver, function () {
                return [
                    'id' => $this->approver->id ?? null,
                    'name' => $this->approver->name ?? null,
                    'email' => $this->approver->email ?? null,
                ];
            }, null),

            // included transaction details (the FinancialTransaction that was created)
            'transaction' => $this->when($tx, function () use ($tx) {
                return [
                    'id' => $tx->id ?? null,
                    'amount' => $tx->amount ?? null,
                    'status' => $tx->status ?? null,
                    'payment_method' => $tx->payment_method ?? null,
                    'patient_id' => $tx->patient_id ?? null,

                    'fiche_navette_item' => $tx->fiche_navette_item_id ? [
                        'id' => $tx->fiche_navette_item_id,
                        'name' => $tx->ficheNavetteItem?->prestation?->nom
                                    ?? $tx->ficheNavetteItem?->prestation?->display_name
                                    ?? $tx->ficheNavetteItem?->display_name
                                    ?? null,
                    ] : null,

                    'item_dependency' => $tx->item_dependency_id ? [
                        'id' => $tx->item_dependency_id,
                        'name' => $tx->itemDependency?->name
                                    ?? $tx->itemDependency?->display_name
                                    ?? null,
                    ] : null,
                ];
            }, null),

            'approval_document' => $this->approval_document ? url('/storage/' . $this->approval_document) : null,

            // attachment information
            'attachment' => $this->attachment,

            // convenience flattened fields for frontend
            'item_type' => $tx ? ($tx->item_dependency_id ? 'item_dependency' : ($tx->fiche_navette_item_id ? 'fiche_navette_item' : null)) : null,
            'item_name' => $tx ? (
                $tx->item_dependency_id
                    ? ($tx->itemDependency?->name ?? null)
                    : ($tx->fiche_navette_item_id ? ($tx->ficheNavetteItem?->prestation?->nom ?? null) : null)
            ) : null,
        ];
    }
}
