<?php

namespace App\Http\Resources\CONFIGURATION;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enum\ModalityAppointmentStatusEnum; // Make sure to import the enum

class ModalityAppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Patient details
        $patientData = $this->whenLoaded('patient', function () {
            return [
                'first_name' => $this->patient->Firstname,
                'last_name' => $this->patient->Lastname,
                'date_of_birth' => $this->patient->dateOfBirth,
                'parent' => $this->patient->Parent,
                'phone' => $this->patient->phone,
            ];
        }, [
            // Default values if 'patient' is not loaded or null
            'first_name' => 'Unknown',
            'last_name' => 'Unknown',
            'date_of_birth' => 'Unknown',
            'parent' => '',
            'phone' => 'N/A',
        ]);

        // Modality details (replacing 'doctor' from AppointmentResource)
        $modalityData = $this->whenLoaded('modality', function () {
            return [
                'id' => $this->modality->id,
                'name' => $this->modality->name,
                'slot_type' => $this->modality->slot_type,
                // Ensure time_slot_duration is correctly retrieved from modality
                'duration_days' => $this->modality->time_slot_duration,
                'specialization_id' => $this->modality->specialization_id,
                'specialization_name' => $this->whenLoaded('modality.specialization', function () {
                    return optional($this->modality->specialization)->name;
                }, 'N/A'),
            ];
        }, [
            // Default values if 'modality' is not loaded or null
            'id' => null,
            'name' => 'Unknown',
            'slot_type' => null, // Added default for slot_type
            'duration_days' => 1, // Added default for duration_days if modality is not loaded
            'specialization_id' => null,
            'specialization_name' => 'N/A',
        ]);

        return [
            'id' => $this->id,

            // Merged patient data
            'patient_id' => $this->patient->id ?? null, // Keep patient_id directly, null if patient not loaded
            'patient_first_name' => $patientData['first_name'] ?? 'Unknown',
            'patient_last_name' => $patientData['last_name'] ?? 'Unknown',
            'patient_Date_Of_Birth' => $patientData['date_of_birth'] ?? 'Unknown',
            'patient_Parent' => $patientData['parent'] ?? '',
            'patient_phone' => $patientData['phone'] ?? 'N/A',

            // Created by user (conditional loading) - using 'creator' relationship from model
            'created_by_user_name' => $this->whenLoaded('creator', fn() => $this->creator->name, 'Unknown'),
            // Canceled by user (conditional loading) - using 'canceller' relationship from model
            'canceled_by_user_name' => $this->whenLoaded('canceller', fn() => $this->canceller->name, 'Unknown'),
            // Updated by user (conditional loading) - using 'updater' relationship from model
            'updated_by_user_name' => $this->whenLoaded('updater', fn() => $this->updater->name, 'Unknown'),

            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'canceled_at' => $this->canceled_at ? $this->canceled_at->format('Y-m-d H:i:s') : null,

            // Modality data (replacing doctor data)
            'modality_id' => $modalityData['id'],
            'modality_name' => $modalityData['name'],
            'slot_type' => $modalityData['slot_type'], // Ensure slot_type is included
            'specialization_id' => $modalityData['specialization_id'],
            'specialization_name' => $modalityData['specialization_name'],
            'duration_days' => $modalityData['duration_days'], // UNCOMMENT THIS LINE

            'appointment_date' => $this->appointment_date ? $this->appointment_date->format('Y-m-d') : null,
            'appointment_time' => $this->appointment_time, // Ensure 'H:i' format
            'reason' => $this->reason,
            'description' => $this->notes,

            'status' => [
                'name' => $this->status->name ?? 'Unknown',
                'color' => $this->status?->color() ?? 'default',
                'icon' => $this->status?->icon() ?? 'default',
                'value' => $this->status?->value ?? null,
            ],
            // No 'add_to_waitlist', 'importance', or 'is_Daily' fields as requested
        ];
    }
}