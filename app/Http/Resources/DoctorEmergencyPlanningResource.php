<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorEmergencyPlanningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'service_id' => $this->service_id,
            'month' => $this->month,
            'year' => $this->year,
            'planning_date' => optional($this->planning_date)->toDateString(),
            'shift_start_time' => $this->shift_start_time,
            'shift_end_time' => $this->shift_end_time,
            'shift_type' => $this->shift_type,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'shift_duration_minutes' => $this->calculateShiftDuration(),
            'doctor' => $this->whenLoaded('doctor', function () {
                $user = $this->doctor?->user;

                $displayName = $user?->name
                    ?? trim(($user?->nom ?? '').' '.($user?->prenom ?? ''))
                    ?: null;

                return [
                    'id' => $this->doctor->id,
                    'user_id' => $this->doctor->user_id,
                    'name' => $displayName,
                    'display_name' => $displayName,
                    'full_name' => $displayName,
                    'nom' => $user?->nom,
                    'prenom' => $user?->prenom,
                    'email' => $user?->email,
                    'is_active' => $user?->is_active,
                    'avatar' => $user?->avatar,
                ];
            }),
            'doctor_display_name' => $this->doctor?->user?->name
                ?? trim(($this->doctor?->user?->nom ?? '').' '.($this->doctor?->user?->prenom ?? ''))
                ?: null,
            'service' => $this->whenLoaded('service', function () {
                $name = $this->service?->name ?? $this->service?->nom;

                return [
                    'id' => $this->service->id,
                    'name' => $name,
                    'display_name' => $name,
                    'nom' => $this->service->nom ?? null,
                ];
            }),
            'meta' => [
                'created_at' => optional($this->created_at)->toDateTimeString(),
                'updated_at' => optional($this->updated_at)->toDateTimeString(),
            ],
        ];
    }

    private function calculateShiftDuration(): ?int
    {
        if (! $this->shift_start_time || ! $this->shift_end_time) {
            return null;
        }

        try {
            $start = Carbon::parse($this->shift_start_time);
            $end = Carbon::parse($this->shift_end_time);
        } catch (\Throwable $exception) {
            return null;
        }

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }
}
