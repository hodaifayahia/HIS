<?php

namespace App\Http\Resources\Reception;

use App\Http\Resources\Nursing\PatientConsumptionResource;
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
            'payment_status' => $this->payment_status,
            'prestation_id' => $this->prestation_id,
            'package_id' => $this->package_id,
            'remaining_amount' => $this->remaining_amount,
            'paid_amount' => $this->paid_amount,
            'doctor_name' => $this->whenLoaded('doctor', fn () => str_replace('Dr. ', '', $this->doctor->user->name ?? null)),
            'convention_id' => $this->convention_id,
            'insured_id' => $this->insured_id,
            'doctor_id' => $this->doctor_id,
            'status' => $this->status,
            'base_price' => $this->base_price,
            // Default final_price from model; may be overridden below if prestation relation is loaded
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
            'is_nursing_consumption' => (bool) $this->is_nursing_consumption,
            // Stored item-level default payment type (may be null if not set)
            'default_payment_type' => $this->default_payment_type ?? null,
            // Payment type options for client dropdowns
            'payment_types' => [
                ['value' => 'Pré-paiement', 'label' => 'Pré-paiement'],
                ['value' => 'Post-paiement', 'label' => 'Post-paiement'],
                ['value' => 'Versement', 'label' => 'Versement'],
            ],
        ];

        // Add prestation data if this is an individual prestation
        if ($this->isPrestation() && $this->relationLoaded('prestation')) {
            // Get the full price with VAT and consumables VAT
            $priceData = $this->prestation->price_with_vat_and_consumables_variant;

            // For convention prestations, use the stored final_price (patient_price)
            if ($this->convention_id) {
                $finalPrice = (float) $this->final_price;
            } else {
                // For non-convention prestations prefer the consumables-aware TTC when available.
                if (is_array($priceData)) {
                    $finalPrice = (float) ($priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0.0);
                } elseif (is_numeric($priceData)) {
                    // model returned the combined TTC as scalar
                    $finalPrice = (float) $priceData;
                } else {
                    $finalPrice = 0.0;
                }
            }

            // Ensure the top-level final_price reflects the correct price
            $data['final_price'] = (float) $finalPrice;

            $data['prestation'] = [
                'id' => $this->prestation->id,
                'name' => $this->prestation->name,
                'internal_code' => $this->prestation->internal_code,
                'Urgent_Prestation' => $this->prestation->Urgent_Prestation,
                'public_price' => $finalPrice,
                'price_with_vat_and_consumables_variant' => $priceData,
                // NOTE: Do NOT expose the prestation's default_payment_type here as the
                // client should rely on the fiche navette item's stored
                // `default_payment_type` (top-level) or the dependency-level
                // `default_payment_type`. Exposing the prestation default causes the
                // frontend to incorrectly show/prefer the prestation setting.
                // Keep this field absent here to avoid confusion.
                'need_an_appointment' => $this->prestation->need_an_appointment,
                'min_versement_amount' => $this->prestation->min_versement_amount ?? null,
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
            // For packages, ensure final_price reflects the package price, not individual prestation totals
            $data['final_price'] = (float) $this->package->price;
            
            $data['package'] = [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'description' => $this->package->description,
                'price' => $this->package->price,
            ];

            if ($this->package && $this->package->relationLoaded('items')) {
                $data['package']['prestations'] = $this->package->items->map(function ($packageItem) {
                    if ($packageItem->relationLoaded('prestation')) {
                        // Get the full price with VAT and consumables VAT
                        $priceData = $packageItem->prestation->price_with_vat_and_consumables_variant;
                        if (is_array($priceData)) {
                            $finalPrice = (float) ($priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0.0);
                        } elseif (is_numeric($priceData)) {
                            $finalPrice = (float) $priceData;
                        } else {
                            $finalPrice = 0.0;
                        }

                        // Get doctor information for package prestation
                        $doctorInfo = null;
                        if ($packageItem->prestation->relationLoaded('doctor') && $packageItem->prestation->doctor) {
                            $doctorInfo = [
                                'id' => $packageItem->prestation->doctor->id,
                                'name' => $packageItem->prestation->doctor->name,
                            ];
                        }

                        return [
                            'id' => $packageItem->prestation->id,
                            'name' => $packageItem->prestation->name,
                            'internal_code' => $packageItem->prestation->internal_code,
                            'public_price' => $finalPrice,
                            'price_with_vat_and_consumables_variant' => $priceData,
                            'Urgent_Prestation' => $packageItem->prestation->Urgent_Prestation,
                            'need_an_appointment' => $packageItem->prestation->need_an_appointment,
                            'default_payment_type' => $packageItem->prestation->default_payment_type,
                            'service_name' => $packageItem->prestation->service->name ?? null,
                            'specialization_name' => $packageItem->prestation->specialization->name ?? null,
                            'specialization_id' => $packageItem->prestation->specialization_id,
                            'package_item_id' => $packageItem->id,
                            'doctor' => $doctorInfo,
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
                'organism_color' => $this->convention->organisme->organism_color,
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
            $paymentTypes = [
                ['value' => 'Pré-paiement', 'label' => 'Pré-paiement'],
                ['value' => 'Post-paiement', 'label' => 'Post-paiement'],
                ['value' => 'Versement', 'label' => 'Versement'],
            ];
            return $this->dependencies->map(function ($dependency) use ($paymentTypes) {
                $prestationInfo = null;
                $prestationName = 'Unknown Dependency';

                // Get the dependency prestation (the prestation that this dependency refers to)
                if ($dependency->relationLoaded('dependencyPrestation') && $dependency->dependencyPrestation) {
                    $prestation = $dependency->dependencyPrestation;

                    // Get the full price with VAT and consumables VAT
                    $priceData = $prestation->price_with_vat_and_consumables_variant;
                    $finalPrice = $priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0.0;

                    $prestationInfo = [
                        'id' => $prestation->id,
                        'name' => $prestation->name ?? 'Unknown',
                        'internal_code' => $prestation->internal_code ?? 'Unknown',
                        'description' => $prestation->description,
                        'public_price' => $finalPrice,
                        'price_with_vat_and_consumables_variant' => $priceData,
                        'min_versement_amount' => $prestation->min_versement_amount ?? null,
                        'Urgent_Prestation' => $prestation->Urgent_Prestation,
                        'remaining_amount' => (float) ($dependency->remaining_amount ?? 0) ?? 0,
                        'paid_amount' => (float) ($dependency->paid_amount ?? 0),
                        'payment_status' => $prestation->payment_status ?? 'null',
                        'service_id' => $prestation->service_id ?? null,
                        'default_payment_type' => $dependency->default_payment_type ?? null,
                        'specialization_id' => $prestation->specialization_id ?? null,
                        'specialization' => ($prestation->relationLoaded('specialization') && $prestation->specialization) ? [
                            'id' => $prestation->specialization->id,
                            'name' => $prestation->specialization->name,
                        ] : null,
                        'doctor' => ($prestation->relationLoaded('doctor') && $prestation->doctor) ? [
                            'id' => $prestation->doctor->id,
                            'name' => $prestation->doctor->name,
                        ] : null,
                    ];

                    $prestationName = $prestation->name;
                } else {
                    // Fallback: try to load the prestation directly
                    if ($dependency->dependent_prestation_id) {
                        try {
                            $prestation = \App\Models\CONFIGURATION\Prestation::find($dependency->dependent_prestation_id);
                            if ($prestation) {
                                // Get the full price with VAT and consumables VAT
                                $priceData = $prestation->price_with_vat_and_consumables_variant;
                                $finalPrice = $priceData['ttc_with_consumables_vat'] ?? $priceData['ttc'] ?? 0.0;

                                $prestationInfo = [
                                    'id' => $prestation->id,
                                    'name' => $prestation->name,
                                    'is_package' => $dependency->is_package ?? false,
                                    'internal_code' => $prestation->internal_code,
                                    'Urgent_Prestation' => $prestation->Urgent_Prestation,
                                    'description' => $prestation->description,

                                    'public_price' => $finalPrice,
                                    'price_with_vat_and_consumables_variant' => $priceData,
                                    'min_versement_amount' => $prestation->min_versement_amount ?? null,
                                    'service_id' => $prestation->service_id ?? null,
                                    'specialization_id' => $prestation->specialization_id ?? null,
                                    'specialization' => null,
                                    'doctor' => ($prestation->relationLoaded('doctor') && $prestation->doctor) ? [
                                        'id' => $prestation->doctor->id,
                                        'name' => $prestation->doctor->name,
                                    ] : null,
                                ];
                                $prestationName = $prestation->name;
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning('Could not load prestation for dependency', [
                                'dependency_id' => $dependency->id,
                                'dependent_prestation_id' => $dependency->dependent_prestation_id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // Display name priority: custom_name > prestation name > fallback
                $displayName = $prestationName;
                if (! empty($dependency->custom_name) && trim($dependency->custom_name) !== '') {
                    $displayName = $dependency->custom_name;
                }

                // For the returned dependency object include the stored dependency-level default_payment_type
                // (fall back to the dependency prestation's default if the stored value is null)
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
                    // Expose the stored dependency-level default_payment_type (not just prestation's value)
                    'default_payment_type' => $dependency->default_payment_type ?? null,
                    // Expose payment_types array for frontend dropdowns
                    'payment_types' => $paymentTypes,
                ];
            });
        });

        if ($this->relationLoaded('nursingConsumptions')) {
            $this->nursingConsumptions->loadMissing(['product', 'pharmacy']);
            $data['nursing_consumptions'] = $this->nursingConsumptions->map(function ($consumption) use ($request) {
                return (new PatientConsumptionResource($consumption))->toArray($request);
            });
        }

        return $data;
    }
}
