<?php

namespace App\Services\CONFIGURATION;


use App\Models\CONFIGURATION\Modality;
use App\Models\CONFIGURATION\ModalitySchedule;
use App\Models\CONFIGURATION\ModalityAvailableMonth;
use App\Models\CONFIGURATION\AppointmentModalityForce;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ModalityService
{
    /**
     * Create a new modality and its associated data.
     *
     * @param array $data
     * @return Modality
     * @throws \Exception
     */
    public function createModality(array $data): Modality
    {
        return DB::transaction(function () use ($data) {
            $modality = Modality::create([
                'name' => $data['name'],
                'internal_code' => $data['internal_code'],
                'modality_type_id' => $data['modality_type_id'],
                'dicom_ae_title' => $data['dicom_ae_title'] ?? null,
                'port' => $data['port'] ?? null,
                'physical_location_id' => $data['physical_location_id'] ?? null,
                'operational_status' => $data['operational_status'],
                'consumption_type' => $data['consumption_type'] ?? null,
                'consumption_unit' => $data['consumption_unit'] ?? null,
                'specialization_id' => $data['specialization_id'],
                'integration_protocol' => $data['integration_protocol'] ?? null,
                'connection_configuration' => $data['connection_configuration'] ?? null,
                'data_retrieval_method' => $data['data_retrieval_method'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'frequency' => $data['frequency'],
                'time_slot_duration' => $data['time_slot_duration'] ?? null,
                'slot_type' => $data['slot_type'],
                'booking_window' => $data['booking_window'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
                'include_time' => $data['include_time'] ?? false,
                'created_by' => Auth::id(),
            ]);

            $this->handleModalityForce($modality, $data);
            $this->handleAppointmentBookingWindow($modality, $data);
            $this->handleSchedules($modality, $data);

            return $modality;
        });
    }

    /**
     * Update an existing modality and its associated data.
     *
     * @param Modality $modality
     * @param array $data
     * @return Modality
     * @throws \Exception
     */
    public function updateModality(Modality $modality, array $data): Modality
    {
         
        return DB::transaction(function () use ($modality, $data) {
            if (isset($data['avatar'])) {
                $this->updateAvatar($modality, $data['avatar']);
            }

            $modality->update([
                'name' => $data['name'],
                'internal_code' => $data['internal_code'],
                'modality_type_id' => $data['modality_type_id'],
                'dicom_ae_title' => $data['dicom_ae_title'] ?? null,
                'port' => $data['port'] ?? null,
                'physical_location_id' => $data['physical_location_id'] ?? null,
                'operational_status' => $data['operational_status'],
                'specialization_id' => $data['specialization_id'],
                'integration_protocol' => $data['integration_protocol'] ?? null,
                'connection_configuration' => $data['connection_configuration'] ?? null,
                'consumption_type' => $data['consumption_type'] ?? $modality->consumption_type,
                'consumption_unit' => $data['consumption_unit'] ?? $modality->consumption_unit,
                'data_retrieval_method' => $data['data_retrieval_method'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'frequency' => $data['frequency'],
                'time_slot_duration' => $data['time_slot_duration'] ?? null,
                'slot_type' => $data['slot_type'],
                'booking_window' => $data['booking_window'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
                'include_time' => $data['include_time'] ?? false,
                'updated_by' => Auth::id(),
                'avatar' => $data['avatar'] ?? $modality->avatar,
            ]);

            $this->handleModalityForce($modality, $data);
            $this->handleAppointmentBookingWindow($modality, $data);
            $this->handleSchedules($modality, $data);

            return $modality;
        });
    }

    /**
     * Delete a modality and its associated data.
     *
     * @param Modality $modality
     * @return bool|null
     */
    public function deleteModality(Modality $modality): ?bool
    {
        return DB::transaction(function () use ($modality) {
            $modality->schedules()->delete();
            $modality->availableMonths()->delete();
            // $modality->appointmentForce()->delete();

            if ($modality->avatar && Storage::exists('public/modalities/' . $modality->avatar)) {
                Storage::delete('public/modalities/' . $modality->avatar);
            }

            return $modality->delete();
        });
    }

    /**
     * Bulk delete modalities.
     *
     * @param array $ids
     * @return int
     */
    public function bulkDeleteModalities(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            ModalitySchedule::whereIn('modality_id', $ids)->delete();
            ModalityAvailableMonth::whereIn('modality_id', $ids)->delete();
            AppointmentModalityForce::whereIn('modality_id', $ids)->delete();

            // Optionally, delete avatars for bulk deleted modalities
            $modalities = Modality::whereIn('id', $ids)->get();
            foreach ($modalities as $modality) {
                if ($modality->avatar && Storage::exists('public/modalities/' . $modality->avatar)) {
                    Storage::delete('public/modalities/' . $modality->avatar);
                }
            }

            return Modality::whereIn('id', $ids)->delete();
        });
    }

    /**
     * Handle the creation/update of AppointmentModalityForce.
     *
     * @param Modality $modality
     * @param array $data
     * @return void
     */
    private function handleModalityForce(Modality $modality, array $data): void
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
        } else {
            AppointmentModalityForce::where('modality_id', $modality->id)->delete();
        }
    }

    /**
     * Handle the creation/update of ModalityAvailableMonth.
     *
     * @param Modality $modality
     * @param array $data
     * @return void
     */
    private function handleAppointmentBookingWindow(Modality $modality, array $data): void
    {
        if (isset($data['appointment_booking_window']) && is_array($data['appointment_booking_window'])) {
            ModalityAvailableMonth::where('modality_id', $modality->id)->delete();
            $appointmentMonthsData = [];
            foreach ($data['appointment_booking_window'] as $monthData) {
                $appointmentMonthsData[] = [
                    'modality_id' => $modality->id,
                    'month' => $monthData['month'],
                    'year' => $monthData['year'],
                    'is_available' => $monthData['is_available'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($appointmentMonthsData)) {
                ModalityAvailableMonth::insert($appointmentMonthsData);
            }
        } else {
             // If no booking window is provided, clear existing ones
            ModalityAvailableMonth::where('modality_id', $modality->id)->delete();
        }
    }

    /**
     * Handle the creation/update of ModalitySchedules based on frequency.
     *
     * @param Modality $modality
     * @param array $data
     * @return void
     */
    private function handleSchedules(Modality $modality, array $data): void
    {
        // Delete existing schedules before creating new ones for an update scenario,
        // or just ensure a clean slate for creation.
        $modality->schedules()->delete();

        if ($data['frequency'] === 'Monthly' && isset($data['customDates']) && !empty($data['customDates'])) {
            $this->createCustomSchedules($modality, $data['customDates']);
        } elseif (($data['frequency'] === 'Daily' || $data['frequency'] === 'Weekly') && isset($data['schedules']) && !empty($data['schedules'])) {
            $this->createRegularSchedules($modality, $data['schedules']);
        }
    }

    /**
     * Create regular schedules (Daily or Weekly) for a modality.
     *
     * @param Modality $modality
     * @param array $schedulesData
     * @return void
     */
    private function createRegularSchedules(Modality $modality, array $schedulesData): void
    {
        $processedSchedules = collect($schedulesData)
            ->groupBy('day_of_week')
            ->map(function ($daySchedules, $dayOfWeek) use ($modality) {
                return $daySchedules->map(function ($schedule) use ($modality) {
                    return [
                        'modality_id' => $modality->id,
                        'day_of_week' => $schedule['day_of_week'],
                        'shift_period' => $schedule['shift_period'] ?? 'morning',
                        'start_time' => $this->formatTimeString($schedule['start_time']),
                        'end_time' => $this->formatTimeString($schedule['end_time']),
                        'time_slot_duration' => 12, // Assuming a default, adjust if dynamic
                        'slot_type' => $modality->slot_type,
                        'is_active' => $schedule['is_active'] ?? true,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
            })
            ->flatten(1)
            ->all();

        if (!empty($processedSchedules)) {
            ModalitySchedule::insert($processedSchedules);
        }
    }

    /**
     * Create custom (Monthly) schedules for a modality.
     *
     * @param Modality $modality
     * @param array $customDatesData
     * @return void
     */
    private function createCustomSchedules(Modality $modality, array $customDatesData): void
    {
        $processedSchedules = collect($customDatesData)
            ->groupBy('date')
            ->map(function ($dateSchedules, $date) use ($modality) {
                return $dateSchedules->map(function ($dateInfo) use ($modality) {
                    $parsedDate = Carbon::parse($dateInfo['date']);
                    $dayOfWeek = strtolower($parsedDate->format('l'));

                    return [
                        'modality_id' => $modality->id,
                        'date' => $parsedDate->format('Y-m-d'),
                        'day_of_week' => $dayOfWeek,
                        'shift_period' => $dateInfo['shift_period'] ?? 'morning',
                        'start_time' => $this->formatTimeString($dateInfo['start_time']),
                        'end_time' => $this->formatTimeString($dateInfo['end_time']),
                        'time_slot_duration' => 12, // Assuming a default, adjust if dynamic
                        'slot_type' => $modality->slot_type,
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
            })
            ->flatten(1)
            ->all();

        if (!empty($processedSchedules)) {
            ModalitySchedule::insert($processedSchedules);
        }
    }

    /**
     * Handle avatar file upload and deletion.
     *
     * @param Modality $modality
     * @param \Illuminate\Http\UploadedFile $avatarFile
     * @return void
     */
    private function updateAvatar(Modality $modality, $avatarFile): void
    {
        if ($modality->avatar && Storage::exists('public/modalities/' . $modality->avatar)) {
            Storage::delete('public/modalities/' . $modality->avatar);
        }
        $avatarPath = $avatarFile->store('modalities', 'public');
        $modality->avatar = basename($avatarPath);
    }

    /**
     * Helper function to ensure time format is H:i.
     *
     * @param string $timeString
     * @return string
     */
    private function formatTimeString(string $timeString): string
    {
        $time = Carbon::parse($timeString);
        return $time->format('H:i');
    }
}