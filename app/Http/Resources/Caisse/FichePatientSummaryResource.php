<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class FichePatientSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Returns patient summary: patient name, total, paid, remaining, services and fiche meta.
     */
    public function toArray($request)
    {
        // compute paid amount if payments relation exists, otherwise try to fallback to 0
        $paid = 0;
        if (isset($this->payments) && is_iterable($this->payments)) {
            $paid = collect($this->payments)->sum('amount');
        }

        // fallback: compute paid from items->paid_amount if present
        if ($paid === 0 && isset($this->items) && is_iterable($this->items)) {
            $itemsPaid = collect($this->items)->pluck('paid_amount')->filter()->map(fn($v) => (float)$v)->sum();
            if ($itemsPaid > 0) $paid = $itemsPaid;
        }

        $total = (float)($this->total_amount ?? 0);
        $remaining = max(0, $total - $paid);

        // list services involved in this fiche
        $services = [];
        if (isset($this->items) && is_iterable($this->items)) {
            $services = collect($this->items)
                ->map(fn($item) => optional($item->prestation)->service)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn($s) => [
                    'id' => $s->id ?? null,
                    'name' => $s->name ?? null,
                ])->all();
        }

        $patientName = null;
        if (isset($this->patient)) {
            $patientName = trim(($this->patient->Firstname ?? '') . ' ' . ($this->patient->Lastname ?? ''));
        }

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'fiche_date' => $this->fiche_date,
            'status' => $this->status,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'patient' => [
                'id' => $this->patient->id ?? null,
                'name' => $patientName,
            ],
            'services' => $services,
            'notes' => $this->notes ?? null,
            'creator' => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ],
        ];
    }
}