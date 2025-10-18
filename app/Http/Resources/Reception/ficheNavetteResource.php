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
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient ? $this->patient->Firstname . ' ' . $this->patient->Lastname : null,
            'patient_balance'=>$this->patient->balance,
            'creator_id' => $this->creator_id,
            'creator_name' => $this->creator ? $this->creator->name : null,
            'status' => $this->status,
            'fiche_date' => $this->fiche_date,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'items' => $this->when($this->relationLoaded('items'), function () {
            //     return $this->items->map(function ($item) {
            //         return [
            //             'id' => $item->id,
            //             'prestation_id' => $item->prestation_id,
            //             'prestation' => $item->prestation ? [
            //                 'id' => $item->prestation->id,
            //                 'name' => $item->prestation->name,
            //                 'internal_code' => $item->prestation->internal_code,
            //                 'price' => $item->prestation->public_price,
            //                 'specialization_name' => $item->prestation->specialization->name ?? null,
            //             ] : null,
            //             'package_id' => $item->package_id,
            //             'doctor_id' => $item->doctor_id,
            //             'custom_name' => $item->custom_name,
            //             'status' => $item->status,
            //             'base_price' => $item->base_price,
            //             'final_price' => $item->final_price,
            //             'patient_share' => $item->patient_share,
            //             // Get dependencies from item_dependencies table
            //             'dependencies' => $this->when($item->relationLoaded('dependencies'), function () use ($item) {
            //                 return $item->dependencies->map(function ($dependency) {
            //                     return [
            //                         'id' => $dependency->id,
            //                         'dependency_type' => $dependency->dependency_type,
            //                         'notes' => $dependency->notes,
            //                         'dependency_prestation' => $dependency->dependencyPrestation ? [
            //                             'id' => $dependency->dependencyPrestation->id,
            //                             'name' => $dependency->dependencyPrestation->name,
            //                             'internal_code' => $dependency->dependencyPrestation->internal_code,
            //                             'price' => $dependency->dependencyPrestation->public_price,
            //                             'is_package' => $dependency->is_package
            //                         ] : null,
            //                     ];
            //                 });
            //             }),
            //         ];
            //     });
            // }),
        ];
    }
}
