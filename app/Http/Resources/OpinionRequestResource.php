<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpinionRequestResource extends JsonResource
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
            'sender_doctor_id' => $this->sender_doctor_id,
            'reciver_doctor_id' => $this->reciver_doctor_id,
            'sender_doctor_name' => $this->senderDoctor->user->name ?? 'Unknown Doctor',
            'reciver_doctor_name' => $this->receiverDoctor->user->name ?? 'Unknown Doctor',
            'request' => $this->request,
            'reply' => $this->Reply,
            'patient_id' => $this->patient_id ?? 'Unknown Patient',
            'status' => $this->status,
            'appointment_id' => $this->appointment_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
