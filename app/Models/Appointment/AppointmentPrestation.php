<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment;
use App\Models\CONFIGURATION\Prestation;

class AppointmentPrestation extends Model
{
    protected $fillable = [
        'appointment_id',
        'description',
        'prestation_id',
    ];

    protected $attributes = [
        'description' => '', // Default empty string instead of null
    ];

    public function appointment()
    {
        return $this->belongsTo(\App\Models\Appointment::class, 'appointment_id');
    }

    public function prestation()
    {
        return $this->belongsTo(\App\Models\CONFIGURATION\Prestation::class, 'prestation_id');
    }
}
