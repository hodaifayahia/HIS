<?php

namespace App\Http\Resources\INFRASTRUCTURE;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bed_identifier' => $this->bed_identifier,
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'room' => [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
                'room_type' => $this->room->room_type,
                'service' => [
                    'id' => $this->room->service->id ?? null,
                    'name' => $this->room->service->name ?? null,
                ]
            ],
            'current_patient' => $this->when($this->currentPatient, [
                'id' => $this->currentPatient->id ?? null,
                'name' => $this->currentPatient->name ?? null,
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
