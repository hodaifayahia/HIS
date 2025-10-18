<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;

class ModalitySchedule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'modality_schedules';
    protected $fillable = [
        'modality_id',
        'day_of_week',
        'shift_period',
        'start_time',
        'end_time',
        'date',
        'slot_type',
        'is_active',
        'break_duration',
        'break_times',
        'excluded_dates',
        'modified_times',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
        'date' => 'date',
        'break_times' => 'array',
        'excluded_dates' => 'array',
        'modified_times' => 'array',
        'is_active' => 'boolean',
        'break_duration' => 'integer',
        'day_of_week' => 'string',
        'shift_period' => 'string'
    ];
    /**
     * Get the modality that owns the schedule.
     */
    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

}
