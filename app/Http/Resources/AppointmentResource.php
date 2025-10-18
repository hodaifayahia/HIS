<?php

namespace App\Http\Resources;

use App\AppointmentSatatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        try {
            // Patient details with comprehensive null checking
            $patientData = $this->getPatientData();

            // Doctor details with comprehensive null checking
            $doctorData = $this->getDoctorData();

            // Waitlist details with comprehensive null checking
            $waitlistData = $this->getWaitlistData();

            // Handle status safely
            $statusData = $this->getStatusData();

            // Build prestation / fiche info if relation loaded
            $prestationsInfo = [];
            $canStart = false;
            if ($this->relationLoaded('appointmentPrestations')) {
                foreach ($this->appointmentPrestations as $ap) {
                    $pre = $ap->prestation;
                    $item = null;
                    $ficheStatus = null;
                    if ($pre) {
                        $item = \App\Models\Reception\ficheNavetteItem::where('prestation_id', $pre->id)
                            ->where('patient_id', $this->patient?->id)
                            ->with('ficheNavette')
                            ->first();

                        if ($item && $item->ficheNavette) {
                            $ficheStatus = $item->ficheNavette->status ?? null;
                        }
                    }

                    $prestationsInfo[] = [
                        'appointment_prestation_id' => $ap->id,
                        'prestation_id' => $pre?->id ?? null,
                        'prestation_name' => $pre?->name ?? $pre?->display_name ?? null,
                        // Include specialization info for check-in logic
                        'specialization_id' => $pre?->specialization_id ?? null,
                        'specialization_name' => $pre?->specialization?->name ?? null,
                        // expose the prestation's configured default payment type
                        'default_payment_type' => $pre?->default_payment_type ?? null,
                            'fiche_navette_item' => $item ? (function() use ($item, $ficheStatus, $pre) {
                            // Normalize status to a numeric value when possible and keep raw value
                            $rawStatus = $item->status;
                            $statusValue = null;
                            if (is_numeric($rawStatus)) {
                                $statusValue = (int) $rawStatus;
                            } else {
                                $s = strtolower((string) $rawStatus);
                                if (in_array($s, ['working', 'onworking', 'on_working', 'on working', 'on_working', 'on-working', 'on working'], true)) {
                                    $statusValue = 5; // ONWORKING
                                } elseif (in_array($s, ['pending', 'wait', 'waiting'], true)) {
                                    $statusValue = 3; // PENDING
                                } elseif (in_array($s, ['done', 'completed', 'complete'], true)) {
                                    $statusValue = 4; // DONE
                                } else {
                                    // leave null if unknown
                                    $statusValue = null;
                                }
                            }

                            // Normalize payment status (keep raw and lowercased form)
                            $rawPayment = $item->payment_status ?? null;
                            $paymentNormalized = $rawPayment !== null ? strtolower((string) $rawPayment) : null;
                            
                            // Expose payment status only when the prestation expects pre-payment
                            $rawPayment = null;
                            $paymentNormalized = null;
                            $defaultPaymentType = $pre?->default_payment_type ?? null;
                            if ($defaultPaymentType !== null) {
                                $ptype = strtolower((string) $defaultPaymentType);
                                $ptype = str_replace([' ', '-', 'é', 'è', 'ê', 'à'], ['', '', 'e', 'e', 'e', 'a'], $ptype);
                                if (strpos($ptype, 'prepaiement') !== false) {
                                    $rawPayment = $item->payment_status ?? null;
                                    $paymentNormalized = $rawPayment !== null ? strtolower((string) $rawPayment) : null;
                                }
                            }

                            return [
                                'id' => $item->id,
                                'status_raw' => $rawStatus,
                                'status' => $statusValue,
                                'payment_status' => $rawPayment ?? null,
                                'payment_status_normalized' => $paymentNormalized,
                                'fiche_navette_status' => $ficheStatus,
                            ];
                        })() : null,
                    ];
                }

                // determine canStart: all prestations must have a fiche item where
                // - fiche_navette_item.payment_status is 'payed' or 'partial'
                // - fiche_navette_item.status == ONWORKING (value 5)
                // - fiche_navette.status == ONWORKING (value 5)
                $canStart = false;
                try {
                    if (count($prestationsInfo) > 0) {
                        $canStart = collect($prestationsInfo)->every(function ($p) {
                            if (! isset($p['fiche_navette_item']) || ! is_array($p['fiche_navette_item'])) return false;

                            $item = $p['fiche_navette_item'];

                            // Determine normalized payment status (we provided payment_status_normalized)
                            $paymentNormalized = $item['payment_status_normalized'] ?? (isset($item['payment_status']) ? strtolower((string) $item['payment_status']) : null);

                            // Only require payment when the prestation's default payment type is Pré-paiement.
                            $paymentType = $p['default_payment_type'] ?? null;
                            $isPrePaid = false;
                            if ($paymentType !== null) {
                                $ptype = strtolower((string) $paymentType);
                                // normalize: remove spaces/hyphens and common accents to match variants
                                $ptype = str_replace([' ', '-', 'é', 'è', 'ê', 'à'], ['', '', 'e', 'e', 'e', 'a'], $ptype);
                                if (strpos($ptype, 'prepaiement') !== false || strpos($ptype, 'prepaiement') !== false) {
                                    $isPrePaid = true;
                                }
                            }

                            $allowedPayment = false;
                            if ($isPrePaid) {
                                if ($paymentNormalized !== null) {
                                    $allowedPayment = (strpos($paymentNormalized, 'paid') !== false)
                                        || (strpos($paymentNormalized, 'paye') !== false)
                                        || (strpos($paymentNormalized, 'partial') !== false)
                                        || (strpos($paymentNormalized, 'parti') !== false);
                                } else {
                                    $allowedPayment = false;
                                }
                            } else {
                                // payment not required for non pre-paid prestations
                                $allowedPayment = true;
                            }

                            // Determine numeric status value: prefer 'status' (numeric) then attempt to interpret 'status_raw'
                            $statusVal = null;
                            if (isset($item['status']) && is_numeric($item['status'])) {
                                $statusVal = (int) $item['status'];
                            } elseif (isset($item['status_raw'])) {
                                $sr = strtolower((string) $item['status_raw']);
                                if (in_array($sr, ['working', 'onworking', 'on_working', 'on working', 'on-working'], true)) {
                                    $statusVal = 5;
                                } elseif (in_array($sr, ['pending', 'wait', 'waiting'], true)) {
                                    $statusVal = 3;
                                } elseif (in_array($sr, ['done', 'completed', 'complete'], true)) {
                                    $statusVal = 4;
                                }
                            }

                            // Only require the fiche_navette_item to be ONWORKING (5)
                            return $allowedPayment && $statusVal === 5;
                        });
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed computing can_start_consultation for appointment '.$this->id, [
                        'error' => $e->getMessage(),
                    ]);
                    $canStart = false;
                }
            }

            return [
                'id' => $this->id ?? null,

                // Patient data
                'patient_id' => $patientData['id'],
                'patient_first_name' => $patientData['first_name'],
                'patient_last_name' => $patientData['last_name'],
                'patient_Date_Of_Birth' => $patientData['date_of_birth'],
                'Parent' => $patientData['parent'],
                'phone' => $patientData['phone'],

                // User relationships with safe access
                'created_by' => $this->getCreatedByName(),
                'canceled_by' => $this->getCanceledByName(),
                'updated_by' => $this->getUpdatedByName(),

                // Timestamps
                'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
                'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,

                // Doctor data
                'doctor_id' => $doctorData['id'],
                'doctor_name' => $doctorData['name'],
                'specialization_id' => $doctorData['specialization_id'],

                // Appointment details
                'appointment_date' => $this->appointment_date ?? null,
                'appointment_time' => $this->appointment_time ?? null,
                'add_to_waitlist' => (bool) ($this->add_to_waitlist ?? false),
                'reason' => $this->reason ?? '',
                'description' => $this->notes ?? '',

                // Status data
                'status' => $statusData,

                // Waitlist data
                'importance' => $waitlistData['importance'],
                'is_Daily' => $waitlistData['is_daily'],

                // Prestation/fiche info
                'prestations' => $prestationsInfo,
                'can_start_consultation' => $canStart,
            ];
        } catch (\Exception $e) {
            \Log::error('Error transforming appointment resource', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return minimal safe data
            return [
                'id' => $this->id ?? null,
                'error' => 'Data transformation error',
                'status' => $this->getDefaultStatusData(),
            ];
        }
    }

    /**
     * Get patient data safely
     */
    private function getPatientData(): array
    {
        try {
            if (isset($this->patient) && $this->patient !== null) {
                return [
                    'id' => $this->patient->id ?? null,
                    'first_name' => $this->patient->Firstname ?? 'Unknown',
                    'last_name' => $this->patient->Lastname ?? 'Unknown',
                    'date_of_birth' => $this->patient->dateOfBirth ?? 'Unknown',
                    'parent' => $this->patient->Parent ?? '',
                    'phone' => $this->patient->phone ?? 'N/A',
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing patient data', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDefaultPatientData();
    }

    /**
     * Get doctor data safely
     */
    private function getDoctorData(): array
    {
        try {
            if (isset($this->doctor) && $this->doctor !== null) {
                $doctorName = 'Unknown';

                // Safely access doctor.user.name
                if (isset($this->doctor->user) && $this->doctor->user !== null) {
                    $doctorName = $this->doctor->user->name ?? 'Unknown';
                }

                return [
                    'id' => $this->doctor->id ?? null,
                    'name' => $doctorName,
                    'specialization_id' => $this->doctor->specialization_id ?? null,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing doctor data', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDefaultDoctorData();
    }

    /**
     * Get waitlist data safely
     */
    private function getWaitlistData(): array
    {
        try {
            if (isset($this->waitlist) && $this->waitlist !== null) {
                return [
                    'importance' => $this->waitlist->importance ?? 'Unknown',
                    'is_daily' => (bool) ($this->waitlist->is_Daily ?? false),
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing waitlist data', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDefaultWaitlistData();
    }

    /**
     * Get created by name safely
     */
    private function getCreatedByName(): string
    {
        try {
            if (isset($this->createdByUser) && $this->createdByUser !== null) {
                return $this->createdByUser->name ?? 'Unknown';
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing createdByUser', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        return 'Unknown';
    }

    /**
     * Get canceled by name safely
     */
    private function getCanceledByName(): ?string
    {
        try {
            if (isset($this->canceledByUser) && $this->canceledByUser !== null) {
                return $this->canceledByUser->name ?? null;
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing canceledByUser', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get updated by name safely
     */
    private function getUpdatedByName(): ?string
    {
        try {
            if (isset($this->updatedByUser) && $this->updatedByUser !== null) {
                return $this->updatedByUser->name ?? null;
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing updatedByUser', [
                'appointment_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get default patient data structure
     */
    private function getDefaultPatientData(): array
    {
        return [
            'id' => null,
            'first_name' => 'Unknown',
            'last_name' => 'Unknown',
            'date_of_birth' => 'Unknown',
            'parent' => '',
            'phone' => 'N/A',
        ];
    }

    /**
     * Get default doctor data structure
     */
    private function getDefaultDoctorData(): array
    {
        return [
            'id' => null,
            'name' => 'Unknown',
            'specialization_id' => null,
        ];
    }

    /**
     * Get default waitlist data structure
     */
    private function getDefaultWaitlistData(): array
    {
        return [
            'importance' => 'Unknown',
            'is_daily' => false,
        ];
    }

    /**
     * Get status data with safe enum handling
     */
    private function getStatusData(): array
    {
        try {
            // Handle null status
            if ($this->status === null) {
                return $this->getDefaultStatusData();
            }

            // If status is already an enum instance, use it directly
            if ($this->status instanceof AppointmentSatatusEnum) {
                return [
                    'name' => $this->status->name ?? 'Unknown',
                    'color' => method_exists($this->status, 'color') ? $this->status->color() : 'secondary',
                    'icon' => method_exists($this->status, 'icon') ? $this->status->icon() : 'fa-question',
                    'value' => $this->status->value ?? null,
                ];
            }

            // If status is a primitive value, use manual mapping
            $statusValue = is_numeric($this->status) ? (int) $this->status : $this->status;

            return [
                'name' => $this->getStatusName($statusValue),
                'color' => $this->getStatusColor($statusValue),
                'icon' => $this->getStatusIcon($statusValue),
                'value' => $statusValue,
            ];

        } catch (\Exception $e) {
            \Log::error('Error processing appointment status', [
                'appointment_id' => $this->id ?? 'unknown',
                'status_value' => $this->status ?? 'null',
                'status_type' => gettype($this->status ?? 'null'),
                'error' => $e->getMessage(),
            ]);

            return $this->getDefaultStatusData();
        }
    }

    /**
     * Get default status data
     */
    private function getDefaultStatusData(): array
    {
        return [
            'name' => 'Unknown',
            'color' => 'secondary',
            'icon' => 'fa-question',
            'value' => null,
        ];
    }

    /**
     * Get status name from value
     */
    private function getStatusName($statusValue): string
    {
        $statusMap = [
            0 => 'SCHEDULED',
            1 => 'CONFIRMED',
            2 => 'CANCELED',
            3 => 'PENDING',
            4 => 'DONE',
            5 => 'ONWORKING',
        ];

        return $statusMap[$statusValue] ?? 'Unknown';
    }

    /**
     * Get status color from value
     */
    private function getStatusColor($statusValue): string
    {
        $colorMap = [
            0 => 'primary',
            1 => 'success',
            2 => 'danger',
            3 => 'warning',
            4 => 'info',
            5 => 'warning',
        ];

        return $colorMap[$statusValue] ?? 'secondary';
    }

    /**
     * Get status icon from value
     */
    private function getStatusIcon($statusValue): string
    {
        $iconMap = [
            0 => 'fa-calendar-check',
            1 => 'fa-check',
            2 => 'fa-ban',
            3 => 'fa-hourglass-half',
            4 => 'fa-check-circle',
            5 => 'fa-tools',
        ];

        return $iconMap[$statusValue] ?? 'fa-question';
    }
}
