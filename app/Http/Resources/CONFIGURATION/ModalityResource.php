<?php

namespace App\Http\Resources\CONFIGURATION;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModalityResource extends JsonResource
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
            'name' => $this->name,
            'internal_code' => $this->internal_code,
            'modality_type_id' => $this->modality_type_id,
            'dicom_ae_title' => $this->dicom_ae_title,
            'port' => $this->port,
            'physical_location_id' => $this->physical_location_id,
            'operational_status' => $this->operational_status,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,

            // --- Additional Fields for Modality Configuration ---
            'specialization_id' => $this->specialization_id,
            'integration_protocol' => $this->integration_protocol,
            'connection_configuration' => $this->connection_configuration,
            'data_retrieval_method' => $this->data_retrieval_method,
            'ip_address' => $this->ip_address,
            'consumption_type' => $this->consumption_type,
            'consumption_unit' => $this->consumption_unit,
            'frequency' => $this->frequency,
            'time_slot_duration' => $this->time_slot_duration,
            'slot_type' => $this->slot_type,
            'booking_window' => $this->booking_window,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'avatar' => $this->image_path ? asset($this->image_path) : null, // Assuming image_path is the path to the avatar

            // --- Appointment Force Fields ---
            'start_time_force' => $this->appointmentModalityForce->start_time ?? null,
            'end_time_force' => $this->appointmentModalityForce->end_time ?? null,
            'number_of_patients' => $this->appointmentModalityForce->number_of_patients ?? null,

            // --- Relationships ---
            'modality_type' => $this->whenLoaded('modalityType', function () {
                return [
                    'id' => $this->modalityType->id,
                    'name' => $this->modalityType->name,
                ];
            }),
            'physical_location' => $this->whenLoaded('physicalLocation', function () {
                return [
                    'id' => $this->physicalLocation->id,
                    'name' => $this->physicalLocation->name,
                ];
            }),
            'specialization' => $this->whenLoaded('specialization', function () {
                return [
                    'id' => $this->specialization->id,
                    'name' => $this->specialization->name,
                ];
            }),
            'schedules' => $this->whenLoaded('schedules', function () {
                return $this->schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'day_of_week' => $schedule->day_of_week,
                        'shift_period' => $schedule->shift_period,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'date' => self::formatDate($schedule->date),
                        'time_slot_duration' => $schedule->time_slot_duration,
                        'slot_type' => $schedule->slot_type,
                        'is_active' => (bool) $schedule->is_active,
                    ];
                });
            }),
            'available_months' => $this->whenLoaded('availableMonths', function () {
                return $this->availableMonths->map(function ($month) {
                    return [
                        'id' => $month->id,
                        'month' => $month->month,
                        'year' => $month->year,
                        'is_available' => (bool) $month->is_available,
                    ];
                });
            }),
        ];
    }

    protected static function formatDate($date)  {
        // This function is used to format the date if needed
        return $date ? $date->format('Y-m-d') : null;
    }
}