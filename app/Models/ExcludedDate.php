<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcludedDate extends Model
{
    protected $fillable = [
        'doctor_id',
        'end_date',
        'start_date',
        'start_time',
        'end_time',
        'exclusionType',
        'number_of_patients_per_day',
        'shift_period',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'apply_for_all_years',
        'reason',
        // Morning shift fields
        'morning_start_time',
        'morning_end_time',
        'morning_patients',
        'is_morning_active',
        // Afternoon shift fields
        'afternoon_start_time',
        'afternoon_end_time',
        'afternoon_patients',
        'is_afternoon_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
