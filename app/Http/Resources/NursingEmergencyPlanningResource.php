<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NursingEmergencyPlanningResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nurse_id' => $this->nurse_id,
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
            'nurse' => $this->whenLoaded('nurse', function () {
                if (! $this->nurse) {
                    return null;
                }

                $displayName = $this->nurse->name
                    ?? trim(($this->nurse->nom ?? '').' '.($this->nurse->prenom ?? ''))
                    ?: null;

                return [
                    'id' => $this->nurse->id,
                    'name' => $displayName,
                    'display_name' => $displayName,
                    'email' => $this->nurse->email,
                    'is_active' => $this->nurse->is_active,
                    'avatar' => $this->nurse->avatar,
                ];
            }),
            'nurse_display_name' => $this->nurse?->name
                ?? trim(($this->nurse?->nom ?? '').' '.($this->nurse?->prenom ?? ''))
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
