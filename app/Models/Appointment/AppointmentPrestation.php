<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment;
use App\Models\Prestation;

class AppointmentPrestation extends Model
{
    protected $fillable = [
        'appointment_id',
        'description',
        'prestation_id',
    ];

    public function appointment()
    {
        return $this->belongsTo(\App\Models\Appointment::class, 'appointment_id');
    }

    public function prestation()
    {
        return $this->belongsTo(\App\Models\Prestation::class, 'prestation_id');
    }
}
