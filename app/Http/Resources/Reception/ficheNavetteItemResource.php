<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class ficheNavetteItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'fiche_navette_id' => $this->fiche_navette_id,
            'prestation_id' => $this->prestation_id,
            'package_id' => $this->package_id,
            'convention_id' => $this->convention_id,
            'insured_id' => $this->insured_id,
            'doctor_id' => $this->doctor_id,
            'status' => $this->status,
            'base_price' => $this->base_price,
            'final_price' => $this->final_price,
            'patient_share' => $this->patient_share,
            'prise_en_charge_date' => $this->prise_en_charge_date?->format('Y-m-d'),
            'family_authorization' => $this->family_authorization,
            'uploaded_file' => $this->uploaded_file,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_package' => $this->isPackage(),
            'is_prestation' => $this->isPrestation(),
        ];

        // Add prestation data if this is an individual prestation
        if ($this->isPrestation() && $this->relationLoaded('prestation')) {
            $data['prestation'] = [
                'id' => $this->prestation->id,
                'name' => $this->prestation->name,
                'internal_code' => $this->prestation->internal_code,
                'public_price' => $this->prestation->public_price,
                'need_an_appointment' => $this->prestation->need_an_appointment,
                'service' => $this->prestation->service ? [
                    'id' => $this->prestation->service->id,
                    'name' => $this->prestation->service->name,
                ] : null,
                'specialization' => $this->prestation->specialization ? [
                    'id' => $this->prestation->specialization->id,
                    'name' => $this->prestation->specialization->name,
                ] : null,
            ];
        }

        // Add package data if this is a package
        if ($this->isPackage() && $this->relationLoaded('package')) {
            $data['package'] = [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'description' => $this->package->description,
                'price' => $this->package->price,
            ];

            if ($this->package && $this->package->relationLoaded('items')) {
                $data['package']['prestations'] = $this->package->items->map(function($packageItem) {
                    if ($packageItem->relationLoaded('prestation')) {
                        return [
                            'id' => $packageItem->prestation->id,
                            'name' => $packageItem->prestation->name,
                            'internal_code' => $packageItem->prestation->internal_code,
                            'public_price' => $packageItem->prestation->public_price,
                            'need_an_appointment' => $packageItem->prestation->need_an_appointment,
                            'service_name' => $packageItem->prestation->service->name ?? null,
                            'specialization_name' => $packageItem->prestation->specialization->name ?? null,
                            'specialization_id' => $packageItem->prestation->specialization_id,
                            'package_item_id' => $packageItem->id,
                        ];
                    }
                    return null;
                })->filter()->values();
            }
        }

        // Add other relationships
        if ($this->relationLoaded('convention')) {
            $data['convention'] = $this->convention ? [
                'id' => $this->convention->id,
                'name' => $this->convention->name,
                'contract_name' => $this->convention->contract_name,
            ] : null;
        }

        if ($this->relationLoaded('doctor')) {
            $data['doctor'] = $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ] : null;
        }

        if ($this->relationLoaded('insuredPatient')) {
            $data['insured_patient'] = $this->insuredPatient ? [
                'id' => $this->insuredPatient->id,
                'first_name' => $this->insuredPatient->first_name,
                'last_name' => $this->insuredPatient->last_name,
            ] : null;
        }

        // Fixed dependencies - Use dependencyPrestation, not parentItem->prestation
        $data['dependencies'] = $this->whenLoaded('dependencies', function () {
            return $this->dependencies->map(function ($dependency) {
                $prestationInfo = null;
                $prestationName = 'Unknown Dependency';
                

                // Get the dependency prestation (the prestation that this dependency refers to)
                if ($dependency->relationLoaded('dependencyPrestation') && $dependency->dependencyPrestation) {
                    $prestation = $dependency->dependencyPrestation;
                    
                    $prestationInfo = [
                        'id' => $prestation->id,
                        'name' => $prestation->name ?? 'Unknown',
                        'internal_code' => $prestation->internal_code ?? 'Unknown',
                        'description' => $prestation->description,
                        'public_price' => $prestation->public_price,
                        'service_id' => $prestation->service_id ?? null,
                        'specialization_id' => $prestation->specialization_id ?? null,
                        'specialization' => ($prestation->relationLoaded('specialization') && $prestation->specialization) ? [
                            'id' => $prestation->specialization->id,
                            'name' => $prestation->specialization->name,
                        ] : null,
                    ];
                    
                    $prestationName = $prestation->name;
                } else {
                    // Fallback: try to load the prestation directly
                    if ($dependency->dependent_prestation_id) {
                        try {
                            $prestation = \App\Models\CONFIGURATION\Prestation::find($dependency->dependent_prestation_id);
                            if ($prestation) {
                                $prestationInfo = [
                                    'id' => $prestation->id,
                                    'name' => $prestation->name,
                                    'is_package' => $dependency->is_package ?? false,
                                    'internal_code' => $prestation->internal_code,
                                    'description' => $prestation->description,
                                    'public_price' => $prestation->public_price,
                                    'service_id' => $prestation->service_id ?? null,
                                    'specialization_id' => $prestation->specialization_id ?? null,
                                    'specialization' => null,
                                ];
                                $prestationName = $prestation->name;
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Could not load prestation for dependency', [
                                'dependency_id' => $dependency->id,
                                'dependent_prestation_id' => $dependency->dependent_prestation_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                // Display name priority: custom_name > prestation name > fallback
                $displayName = $prestationName;
                if (!empty($dependency->custom_name) && trim($dependency->custom_name) !== '') {
                    $displayName = $dependency->custom_name;
                }

                return [
                    'id' => $dependency->id,
                    'parent_item_id' => $dependency->parent_item_id,
                    'dependent_prestation_id' => $dependency->dependent_prestation_id,
                    'dependency_type' => $dependency->dependency_type ?? 'prestation',
                    'doctor_id' => $dependency->doctor_id,
                    'base_price' => $dependency->base_price,
                    'final_price' => $dependency->final_price,
                    'status' => $dependency->status,
                    'notes' => $dependency->notes,
                    'custom_name' => $dependency->custom_name,
                    'display_name' => $displayName,
                    'created_at' => $dependency->created_at,
                    'updated_at' => $dependency->updated_at,
                    'dependencyPrestation' => $prestationInfo,
                ];
            });
        });

        return $data;
    }
}
