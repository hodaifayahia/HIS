<?php

namespace App\Models;

use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'date',
        'number_of_patients_per_day',
        'start_time',
        'end_time',
        'shift_period',
        'is_active',
        'break_duration',
        'break_times',
        'excluded_dates',
        'modified_times',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'break_times' => 'array',
        'excluded_dates' => 'array',
        'modified_times' => 'array',
        'is_active' => 'boolean',
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get schedules for a specific doctor and day
     */
    public static function getSchedulesForDoctor(int $doctorId, string $dayOfWeek)
    {
        return self::select('start_time', 'end_time', 'number_of_patients_per_day', 'shift_period')
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->get();
    }

    /**
     * Get active schedules for a doctor
     */
    public static function getActiveSchedulesForDoctor(int $doctorId)
    {
        return self::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Check if a doctor has schedule conflicts
     */
    public static function hasConflict(int $doctorId, string $dayOfWeek, string $shiftPeriod, string $startTime, string $endTime, ?int $excludeId = null)
    {
        $query = self::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('shift_period', $shiftPeriod)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($subQ) use ($startTime, $endTime) {
                      $subQ->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get available time slots for a doctor on a specific date
     */
    public function getAvailableTimeSlots(Carbon $date)
    {
        $slots = [];
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        
        // Calculate slot duration based on number of patients per day
        $totalMinutes = $startTime->diffInMinutes($endTime);
        $slotDuration = $totalMinutes / $this->number_of_patients_per_day;
        
        while ($startTime->lt($endTime)) {
            $slotEnd = $startTime->copy()->addMinutes($slotDuration);
            
            // Check if this slot is during a break
            if (!$this->isDuringBreak($startTime)) {
                $slots[] = [
                    'start' => $startTime->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
            }
            
            $startTime = $slotEnd;
        }
        
        return $slots;
    }

    /**
     * Check if a time is during a break
     */
    private function isDuringBreak(Carbon $time)
    {
        if (!$this->break_times || !$this->break_duration) {
            return false;
        }

        foreach ($this->break_times as $breakStart) {
            $breakStartTime = Carbon::parse($breakStart);
            $breakEndTime = $breakStartTime->copy()->addMinutes($this->break_duration);
            
            if ($time->between($breakStartTime, $breakEndTime)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a date is excluded
     */
    public function isDateExcluded(Carbon $date)
    {
        if (!$this->excluded_dates) {
            return false;
        }

        return in_array($date->format('Y-m-d'), $this->excluded_dates);
    }

    /**
     * Get modified times for a specific date
     */
    public function getModifiedTimesForDate(Carbon $date)
    {
        if (!$this->modified_times) {
            return null;
        }

        $dateString = $date->format('Y-m-d');
        return $this->modified_times[$dateString] ?? null;
    }

    /**
     * Scope for active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for schedules by day of week
     */
    public function scopeByDayOfWeek($query, string $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope for schedules by shift period
     */
    public function scopeByShiftPeriod($query, string $shiftPeriod)
    {
        return $query->where('shift_period', $shiftPeriod);
    }
}
