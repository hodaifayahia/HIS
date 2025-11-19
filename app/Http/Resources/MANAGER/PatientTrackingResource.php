<?php

namespace App\Http\Resources\MANAGER;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientTrackingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fiche_navette_item_id' => $this->fiche_navette_item_id,
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'firstname' => $this->patient->Firstname,
                    'lastname' => $this->patient->Lastname,
                    'full_name' => $this->patient->Firstname.' '.$this->patient->Lastname,
                ];
            }),
            'salle_id' => $this->salle_id,
            'salle' => $this->whenLoaded('salle', function () {
                return [
                    'id' => $this->salle->id,
                    'name' => $this->salle->name,
                    'number' => $this->salle->number,
                    'description' => $this->salle->description,
                ];
            }),
            'specialization_id' => $this->specialization_id,
            'specialization' => $this->whenLoaded('specialization', function () {
                return [
                    'id' => $this->specialization->id,
                    'name' => $this->specialization->name,
                ];
            }),
            'prestation_id' => $this->prestation_id,
            'prestation' => $this->whenLoaded('prestation', function () {
                return [
                    'id' => $this->prestation->id,
                    'name' => $this->prestation->name,
                    'internal_code' => $this->prestation->internal_code,
                ];
            }),
            'fiche_navette_item' => $this->whenLoaded('ficheNavetteItem', function () {
                return [
                    'id' => $this->ficheNavetteItem->id,
                    'status' => $this->ficheNavetteItem->status,
                    'fiche_navette' => $this->whenLoaded('ficheNavetteItem.ficheNavette', function () {
                        return [
                            'id' => $this->ficheNavetteItem->ficheNavette->id,
                            'reference' => $this->ficheNavetteItem->ficheNavette->reference,
                        ];
                    }),
                ];
            }),
            'check_in_time' => $this->check_in_time?->format('Y-m-d H:i:s'),
            'check_out_time' => $this->check_out_time?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->check_in_time && $this->check_out_time
                ? $this->check_in_time->diffInMinutes($this->check_out_time)
                : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
