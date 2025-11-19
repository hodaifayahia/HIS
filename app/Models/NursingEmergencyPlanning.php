<?php

namespace App\Models;

use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingEmergencyPlanning extends Model
{
    use HasFactory;

    protected $fillable = [
        'nurse_id',
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

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeForMonthYear($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('planning_date', [$startDate, $endDate]);
    }

    public function scopeByShiftType($query, $shiftType)
    {
        return $query->where('shift_type', $shiftType);
    }
}
