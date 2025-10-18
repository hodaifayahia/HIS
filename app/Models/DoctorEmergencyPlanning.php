<?php

namespace App\Models;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorEmergencyPlanning extends Model
{
    protected $fillable = [
        'doctor_id',
        'service_id',
        'month',
        'year',
        'planning_date',
        'shift_start_time',
        'shift_end_time',
        'shift_type',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'planning_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Scope for getting planning by month/year
    public function scopeForMonthYear($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    // Scope for getting active plannings
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for getting planning by date range
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('planning_date', [$startDate, $endDate]);
    }

    // Scope for getting planning by shift type
    public function scopeByShiftType($query, $shiftType)
    {
        return $query->where('shift_type', $shiftType);
    }
}
