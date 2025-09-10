<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CONFIGURATION\Modality;
use App\Models\Patient;
use App\Models\User;
use App\AppointmentSatatusEnum; // Assuming AppointmentSatatusEnum is the correct enum now

class MoalityAppointments extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'modality_id',
        'patient_id',
        'notes',
        'appointment_booking_window',
        'appointment_date',
        'appointment_time',
        'end_date', // Added end_date to fillable
        'reason',
        'created_by',
        'canceled_by',
        'updated_by',
        'status',
    ];
    protected $casts = [
        'appointment_date' => 'date',
        'end_date' => 'date', // Added end_date cast
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'canceled_at' => 'datetime',
        'status' => AppointmentSatatusEnum::class

    ];

    public function modality()
    {
        return $this->belongsTo(Modality::class, 'modality_id');
    }
    

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if a specific time/day slot is available for a new appointment.
     *
     * @param int $modalityId
     * @param string $date
     * @param string $time (relevant for 'minutes' slot_type)
     * @param array $excludedStatuses
     * @param string|null $endDate (relevant for 'days' slot_type)
     * @return bool
     */
    public static function isSlotAvailable(int $modalityId, string $date, ?string $time, array $excludedStatuses, ?string $endDate = null): bool
    {
        $modality = Modality::find($modalityId);

        if (!$modality) {
            return false; // Modality not found
        }

        if ($modality->slot_type === 'days') {
            // For 'days' slot type, check for overlapping bookings
            return !self::where('modality_id', $modalityId)
                ->where(function ($query) use ($date, $endDate, $excludedStatuses) {
                    $start = \Illuminate\Support\Carbon::parse($date);
                    $end = $endDate ? \Illuminate\Support\Carbon::parse($endDate) : $start; // If no end date, assume 1 day

                    // Check for existing bookings that overlap with the new requested period
                    $query->where(function ($q) use ($start, $end) {
                            $q->where('appointment_date', '<=', $end->format('Y-m-d'))
                              ->where('end_date', '>=', $start->format('Y-m-d'));
                        })
                        ->whereNotIn('status', $excludedStatuses);
                })
                ->exists();
        } else {
            // For 'minutes' (fixed duration) slot type, check for exact time slot booking
            return !self::where('modality_id', $modalityId)
                ->whereDate('appointment_date', $date)
                ->whereTime('appointment_time', $time)
                ->whereNotIn('status', $excludedStatuses)
                ->exists();
        }
    }

    /**
     * Check if a specific time/day slot is available when updating an existing appointment.
     * Excludes the current appointment being updated from the check.
     *
     * @param int $modalityId
     * @param string $date
     * @param string $time (relevant for 'minutes' slot_type)
     * @param array $excludedStatuses
     * @param int $currentAppointmentId
     * @param string|null $endDate (relevant for 'days' slot_type)
     * @return bool
     */
    public static function isSlotAvailableForUpdate(int $modalityId, string $date, ?string $time, array $excludedStatuses, int $currentAppointmentId, ?string $endDate = null): bool
    {
        $modality = Modality::find($modalityId);

        if (!$modality) {
            return false; // Modality not found
        }

        if ($modality->slot_type === 'days') {
            // For 'days' slot type, check for overlapping bookings, excluding current appointment
            return !self::where('modality_id', $modalityId)
                ->where('id', '!=', $currentAppointmentId) // Exclude the current appointment
                ->where(function ($query) use ($date, $endDate, $excludedStatuses) {
                    $start = \Illuminate\Support\Carbon::parse($date);
                    $end = $endDate ? \Illuminate\Support\Carbon::parse($endDate) : $start;

                    $query->where(function ($q) use ($start, $end) {
                            $q->where('appointment_date', '<=', $end->format('Y-m-d'))
                              ->where('end_date', '>=', $start->format('Y-m-d'));
                        })
                        ->whereNotIn('status', $excludedStatuses);
                })
                ->exists();
        } else {
            // For 'minutes' (fixed duration) slot type, check for exact time slot booking, excluding current appointment
            return !self::where('modality_id', $modalityId)
                ->whereDate('appointment_date', $date)
                ->whereTime('appointment_time', $time)
                ->where('id', '!=', $currentAppointmentId) // Exclude the current appointment
                ->whereNotIn('status', $excludedStatuses)
                ->exists();
        }
    }
}