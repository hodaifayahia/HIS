<?php

namespace App\Observers\CONFIGURATION;

use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalityAvailableMonth;
use App\Models\CONFIGURATION\ModalitySchedule;
use App\Models\CONFIGURATION\AppointmentModalityForce;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ModalityObserver
{
    /**
     * Handle the Modality "created" event.
     */
    public function created(Modality $modality): void
    {
        $data = request()->all(); // Access request data, but be cautious, it's better to pass explicitly if possible

        $this->handleAppointmentForce($modality, $data);
        $this->handleAvailabilityMonths($modality, $data['availability_months'] ?? []);
        $this->handleSchedules($modality, $data);
    }

    /**
     * Handle the Modality "updated" event.
     */
    public function updated(Modality $modality): void
    {
        $data = request()->all();

        // $this->handleAppointmentForce($modality, $data, true);
        $this->handleAvailabilityMonths($modality, $data['availability_months'] ?? [], true);
        $this->handleSchedules($modality, $data, true);
    }

    /**
     * Handle the Modality "deleted" event.
     */
    public function deleted(Modality $modality): void
    {
        $modality->schedules()->delete();
        $modality->availableMonths()->delete();
        // $modality->appointmentModalityForce()->delete();
    }

    /**
     * Handles the creation or updating of appointment forcing.
     */
    private function handleAppointmentForce(Modality $modality, array $data, bool $isUpdate = false): void
    {
        if (isset($data['start_time_force']) && isset($data['end_time_force'])) {
            AppointmentModalityForce::updateOrCreate(
                ['modality_id' => $modality->id],
                [
                    'start_time' => $data['start_time_force'],
                    'end_time' => $data['end_time_force'],
                    'number_of_patients' => $data['number_of_patients'] ?? null,
                    'user_id' => Auth::id(),
                    'is_able_to_force' => true,
                ]
            );
        } elseif ($isUpdate) {
            AppointmentModalityForce::where('modality_id', $modality->id)->delete();
        }
    }

    /**
     * Handles the creation or updating of availability months.
     */
    private function handleAvailabilityMonths(Modality $modality, array $availabilityMonthsData, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            $modality->availableMonths()->delete();
        }

        $availabilityMonths = [];
        foreach ($availabilityMonthsData as $month) {
            $availabilityMonths[] = [
                'modality_id' => $modality->id,
                'month' => $month['value'],
                'year' => $month['year'],
                'is_available' => $month['is_available'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($availabilityMonths)) {
            ModalityAvailableMonth::insert($availabilityMonths);
        }
    }

    /**
     * Handles the creation or updating of schedules (Daily, Weekly, Monthly).
     */
    private function handleSchedules(Modality $modality, array $data, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            $modality->schedules()->delete();
        }

        $schedules = [];
        if (($data['frequency'] ?? null) === 'Monthly' && !empty($data['customDates'])) {
            foreach ($data['customDates'] as $customDate) {
                $parsedDate = Carbon::parse($customDate['date']);
                $dayOfWeek = strtolower($parsedDate->format('l'));

                $schedules[] = [
                    'modality_id' => $modality->id,
                    'day_of_week' => $dayOfWeek,
                    'shift_period' => $customDate['shift_period'],
                    'start_time' => $customDate['start_time'],
                    'end_time' => $customDate['end_time'],
                    'date' => $parsedDate->format('Y-m-d'),
                    'time_slot_duration' => $modality->time_slot_duration,
                    'slot_type' => $modality->slot_type,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } elseif ((($data['frequency'] ?? null) === 'Daily' || ($data['frequency'] ?? null) === 'Weekly') && !empty($data['schedules'])) {
            foreach ($data['schedules'] as $schedule) {
                $schedules[] = [
                    'modality_id' => $modality->id,
                    'day_of_week' => $schedule['day_of_week'],
                    'shift_period' => $schedule['shift_period'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'time_slot_duration' => $modality->time_slot_duration,
                    'slot_type' => $modality->slot_type,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($schedules)) {
            ModalitySchedule::insert($schedules);
        }
    }
}