<?php

namespace App\Http\Resources;

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
        // 1. Conditional loading of relationships:
        // Use $this->whenLoaded() to ensure relationships are only accessed
        // if they have been eager loaded. This prevents accidental lazy loading.

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

        // Doctor details
        $doctorData = $this->whenLoaded('doctor', function () {
            return [
                'id' => $this->doctor->id,
                'name' => optional($this->doctor->user)->name, // Still use optional for nested user
                'specialization_id' => $this->doctor->specialization_id,
            ];
        }, [
            // Default values if 'doctor' is not loaded or null
            'id' => null, // Or 'Unknown' if you prefer
            'name' => 'Unknown',
            'specialization_id' => 'Unknown',
        ]);

        // Waitlist details
        $waitlistData = $this->whenLoaded('waitlist', function () {
            return [
                'importance' => $this->waitlist->importance,
                'is_daily' => $this->waitlist->is_Daily,
            ];
        }, [
            // Default values if 'waitlist' is not loaded or null
            'importance' => 'Unknown',
            'is_daily' => 'Unknown',
        ]);

        return [
            'id' => $this->id,

            // Merged patient data
            'patient_id' => $this->patient->id ?? 'Unknown', // Keep patient_id directly
            'patient_first_name' => $patientData['first_name'],
            'patient_last_name' => $patientData['last_name'],
            'patient_Date_Of_Birth' => $patientData['date_of_birth'],
            'Parent' => $patientData['parent'],
            'phone' => $patientData['phone'],

            // Created by user (conditional loading)
            'created_by' => $this->whenLoaded('createdByUser', fn() => $this->createdByUser->name, 'Unknown'),
            // Canceled by user (conditional loading)
            'canceled_by' => $this->whenLoaded('canceledByUser', fn() => $this->canceledByUser->name, 'Unknown'),
            // Updated by user (conditional loading)
            'updated_by' => $this->whenLoaded('updatedByUser', fn() => $this->updatedByUser->name, 'Unknown'),

            'created_at' => $this->created_at, // Dates are usually not 'Unknown' if the model exists
            'updated_at' => $this->updated_at,

            // Merged doctor data
            'doctor_id' => $doctorData['id'],
            'doctor_name' => $doctorData['name'],
            'specialization_id' => $doctorData['specialization_id'],

            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
            'add_to_waitlist' => (bool) $this->add_to_waitlist, // Cast to boolean for clarity
            'reason' => $this->reason,
            'description' => $this->notes,

            'status' => [
                'name' => $this->status->name ?? 'Unknown',
                'color' => $this->status?->color() ?? 'default',
                'icon' => $this->status?->icon() ?? 'default',
                'value' => $this->status?->value ?? null,
            ],

            // Merged waitlist data
            'importance' => $waitlistData['importance'],
            'is_Daily' => $waitlistData['is_daily'],
        ];
    }
}